<?php

namespace Tests\Feature\Verification;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\VerificationLog;
use App\Services\BlockchainRegistry;
use App\Services\DocumentIssuer;
use App\Services\PermitFields;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PermitVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_links_show_a_database_result_without_checking_the_chain(): void
    {
        $reference = (string) Str::uuid();
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('lookup');

        $this->get(route('permits.verify', $reference))
            ->assertRedirect(route('permits.verify.form', ['ref' => $reference]));

        $this->get(route('permits.verify.form', ['ref' => $reference]))
            ->assertOk()
            ->assertViewIs('permits.result')
            ->assertViewHas('status', 'invalid')
            ->assertSee('Not valid');

        $this->get(route('permits.verify.form', ['ref' => $reference, 'upload' => 1]))
            ->assertOk()
            ->assertViewIs('permits.upload')
            ->assertSee('Verify uploaded file');

        $this->assertDatabaseCount('verification_logs', 0);
    }

    public function test_qr_scan_shows_valid_with_masked_details(): void
    {
        [$document] = $this->issue();
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('lookup');

        $this->get(route('permits.verify.form', ['ref' => $document->document_uuid]))
            ->assertOk()
            ->assertViewIs('permits.result')
            ->assertViewHas('status', 'valid')
            ->assertSee('Valid document')
            ->assertSee($document->document_number)
            ->assertSee('Carlo M***')
            ->assertDontSee('Carlo Mamaril');
    }

    public function test_qr_scan_of_an_old_version_shows_revoked(): void
    {
        [$document] = $this->issue();
        $replacement = app(DocumentIssuer::class)->reissue($document->serviceRequest);

        $this->get(route('permits.verify.form', ['ref' => $document->document_uuid]))
            ->assertOk()
            ->assertViewHas('status', 'revoked')
            ->assertSee(route('permits.verify.form', ['ref' => $replacement->document_uuid]));
    }

    public function test_qr_scan_of_an_expired_permit_shows_expired(): void
    {
        [$document] = $this->issue(expired: true);

        $this->get(route('permits.verify.form', ['ref' => $document->document_uuid]))
            ->assertOk()
            ->assertViewHas('status', 'expired');
    }

    public function test_exact_issued_pdf_checks_the_chain_and_shows_verified_details(): void
    {
        [$document, $pdf] = $this->issue();
        $this->mockChain($document);

        $this->upload($document->document_uuid, $pdf)
            ->assertOk()
            ->assertViewIs('permits.result')
            ->assertViewHas('status', 'valid')
            ->assertDontSee('multipart/form-data', false)
            ->assertSee('Try another file')
            ->assertSee($document->document_number)
            ->assertSee('Carlo M***')
            ->assertDontSee('Carlo Mamaril');

        $this->assertDatabaseHas('verification_logs', [
            'service_request_id' => $document->service_request_id,
            'result' => 'valid',
        ]);
    }

    public function test_scan_requests_the_original_pdf_for_authentication(): void
    {
        [$document] = $this->issue();
        $this->mock(PermitFields::class)->shouldReceive('differences')->once()->andReturn([]);

        $this->post(route('permits.verify.upload'), [
            'ref' => $document->document_uuid,
            'permit' => UploadedFile::fake()->image('scan.png'),
        ])->assertOk()
            ->assertSee('Upload the original issued PDF')
            ->assertDontSee('Fake / Invalid');
    }

    public function test_edited_pdf_is_invalid_and_shows_field_differences(): void
    {
        [$document] = $this->issue();
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('lookup');
        $this->mock(PermitFields::class)->shouldReceive('differences')
            ->once()->withArgs(fn (string $path, string $mime, array $expected): bool => is_file($path)
                && $mime === 'application/pdf' && $expected['holder'] === 'Carlo Mamaril')
            ->andReturn(['Permit holder']);

        $this->upload($document->document_uuid, Pdf::loadHTML('<p>Permit holder: Another person</p>')->output())
            ->assertOk()
            ->assertViewHas('status', 'invalid')
            ->assertSee('Fields that differ')
            ->assertSee('Permit holder');
    }

    public function test_copied_qr_on_a_different_pdf_fails_even_if_extracted_fields_match(): void
    {
        [$document, $original] = $this->issue();
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('lookup');
        $this->mock(PermitFields::class)->shouldReceive('differences')->once()->andReturn([]);

        $this->upload($document->document_uuid, $original."\n% modified")
            ->assertOk()
            ->assertViewHas('status', 'invalid');
    }

    public function test_another_permits_original_pdf_cannot_verify_under_this_qr_reference(): void
    {
        [$first] = $this->issue();
        [, $otherPdf] = $this->issue('Another Holder');
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('lookup');
        $this->mock(PermitFields::class)->shouldReceive('differences')->once()->andReturn([]);

        $this->upload($first->document_uuid, $otherPdf)->assertViewHas('status', 'invalid');
    }

    public function test_reissued_original_is_revoked_and_links_to_its_replacement(): void
    {
        [$document, $pdf] = $this->issue();
        $replacement = app(DocumentIssuer::class)->reissue($document->serviceRequest);
        $document->refresh();
        $this->assertSame($replacement->id, $document->replaced_by);
        $this->assertSame(2, $replacement->version);
        $this->assertNotSame($document->file_hash, $replacement->file_hash);
        $this->mockChain($document);

        $this->upload($document->document_number, $pdf)
            ->assertOk()
            ->assertViewHas('status', 'revoked')
            ->assertSee('Revoked on '.$document->revoked_at->format('F j, Y'))
            ->assertSee(route('permits.verify.form', ['ref' => $replacement->document_uuid]))
            ->assertDontSee('Carlo M***');
    }

    public function test_nonexistent_permit_is_invalid_and_is_logged(): void
    {
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('lookup');

        $this->upload((string) Str::uuid(), Pdf::loadHTML('<p>Unknown permit</p>')->output())
            ->assertOk()
            ->assertViewHas('status', 'invalid');

        $this->assertDatabaseHas('verification_logs', ['service_request_id' => null, 'result' => 'invalid']);
    }

    public function test_chain_hash_mismatch_is_invalid_even_for_a_revoked_permit(): void
    {
        [$document, $pdf] = $this->issue();
        $document->update(['revoked_at' => now()]);
        $this->mockChain($document, ['fileHash' => '0x'.str_repeat('a', 64)]);

        $this->upload($document->document_uuid, $pdf)->assertViewHas('status', 'invalid');
    }

    public function test_chain_status_mismatch_is_invalid_while_revocation_is_pending(): void
    {
        [$document, $pdf] = $this->issue();
        $document->update(['revoked_at' => now()]);
        $this->mockChain($document, ['revokedAt' => 0]);

        $this->upload($document->document_uuid, $pdf)->assertViewHas('status', 'invalid');
    }

    public function test_chain_version_mismatch_is_invalid(): void
    {
        [$document, $pdf] = $this->issue();
        $this->mockChain($document, ['version' => 2]);

        $this->upload($document->document_uuid, $pdf)->assertViewHas('status', 'invalid');
    }

    public function test_node_unavailability_never_confirms_an_exact_pdf(): void
    {
        [$document, $pdf] = $this->issue();
        $this->mockChain($document, ['ok' => false]);

        $this->upload($document->document_uuid, $pdf)
            ->assertViewHas('status', 'unavailable')
            ->assertSee('Cannot verify right now');
    }

    public function test_expired_permit_is_not_valid(): void
    {
        [$document, $pdf] = $this->issue(expired: true);
        $this->mockChain($document);

        $this->upload($document->document_uuid, $pdf)
            ->assertViewHas('status', 'expired')
            ->assertDontSee('Carlo M***');
    }

    public function test_unsupported_file_is_rejected_and_logged(): void
    {
        $this->mock(BlockchainRegistry::class)->shouldNotReceive('lookup');
        $temporaryFile = UploadedFile::fake()->createWithContent('fake.pdf', 'Plain text');

        $this->post(route('permits.verify.upload'), [
            'ref' => (string) Str::uuid(),
            'permit' => new UploadedFile($temporaryFile->getPathname(), 'fake.pdf', 'application/pdf', null, true),
        ])->assertUnprocessable()
            ->assertViewIs('permits.upload')
            ->assertSee('Choose a PDF, JPEG, or PNG file.');

        $this->assertSame('invalid', VerificationLog::sole()->result);
    }

    private function issue(string $holder = 'Carlo Mamaril', bool $expired = false): array
    {
        Storage::fake('supabase');
        view()->getFinder()->prependLocation(base_path('tests/Fixtures/verification'));
        $application = ServiceRequest::factory()->create(['applicant_name' => $holder]);
        $document = app(DocumentIssuer::class)->issue($application, $expired ? now()->subDay() : null);

        return [$document, Storage::disk('supabase')->get($document->file_path)];
    }

    private function mockChain(IssuedDocument $document, array $overrides = []): void
    {
        $anchor = BlockchainRecord::factory()->for($document)->create([
            'contract' => '0x'.str_repeat('1', 40),
            'recorded_at' => now(),
        ]);
        $this->mock(BlockchainRegistry::class)->shouldReceive('lookup')
            ->once()
            ->with($document->document_uuid, $anchor->contract, $anchor->anchorNetwork(), true)
            ->andReturn($overrides + [
                'ok' => true,
                'exists' => true,
                'fileHash' => '0x'.$document->file_hash,
                'fieldsHash' => '0x'.PermitFields::commitment($document->canonical_fields),
                'version' => $document->version,
                'revokedAt' => $document->revoked_at?->timestamp ?? 0,
            ]);
    }

    private function upload(string $reference, string $pdf): TestResponse
    {
        $temporaryFile = UploadedFile::fake()->createWithContent('permit.pdf', $pdf);

        return $this->post(route('permits.verify.upload'), [
            'ref' => $reference,
            'permit' => new UploadedFile($temporaryFile->getPathname(), 'permit.pdf', 'application/pdf', null, true),
        ]);
    }
}
