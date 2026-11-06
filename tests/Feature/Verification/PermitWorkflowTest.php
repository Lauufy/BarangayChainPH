<?php

namespace Tests\Feature\Verification;

use App\Models\AuditLog;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\DocumentIssuer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Covers certificate issuance, PDF delivery, and verification integration. */
class PermitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_document_qr_uses_the_active_environment_host(): void
    {
        Storage::fake('supabase');
        URL::forceRootUrl('https://barangay.example');
        URL::forceScheme('https');
        config(['app.url' => 'https://barangay.example']);

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0007',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $pdf = \Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('output')
            ->once()
            ->andReturn('%PDF-qr-environment-test');

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data): bool {
                $this->assertSame('permits.pdf', $view);

                $verificationUrl = route('permits.verify', [
                    'documentUuid' => $data['document']->document_uuid,
                ]);
                $this->assertStringStartsWith('https://barangay.example/', $verificationUrl);

                $expectedQrCode = (new Builder(
                    writer: new PngWriter,
                    data: $verificationUrl,
                    size: 120,
                    margin: 4
                ))->build()->getDataUri();

                $this->assertSame($expectedQrCode, $data['qrCode']);

                return true;
            })
            ->andReturn($pdf);

        $document = app(DocumentIssuer::class)->issue($application);

        $this->assertTrue(Storage::disk('supabase')->exists($document->file_path));
    }

    public function test_pending_application_can_be_approved_with_an_authenticated_pdf(): void
    {
        Storage::fake('supabase');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0001',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $document = app(DocumentIssuer::class)->issue($application);

        $this->assertInstanceOf(IssuedDocument::class, $document);

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'status' => ServiceRequest::APPROVED,
            'permit_number' => $document->document_number,
        ]);

        $this->assertDatabaseHas('issued_documents', [
            'id' => $document->id,
            'service_request_id' => $application->id,
            'document_uuid' => $document->document_uuid,
            'version' => 1,
        ]);

        $this->assertTrue(Storage::disk('supabase')->exists($document->file_path));

        $pdfContent = Storage::disk('supabase')->get($document->file_path);

        $this->assertStringStartsWith('%PDF', $pdfContent);

        $this->assertSame(
            hash('sha256', $pdfContent),
            $document->file_hash
        );

        $this->assertStringContainsString('/Subtype /Image', $pdfContent);
        $this->assertNotNull($document->qr_code_generated_at);
        $this->assertNull($document->revoked_at);
    }

    public function test_printing_a_legacy_document_without_a_qr_does_not_regenerate_it(): void
    {
        Storage::fake('supabase');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0006',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $legacyDocument = app(DocumentIssuer::class)->issue($application);
        $legacyPdf = '%PDF-legacy-document-without-qr';

        Storage::disk('supabase')->put($legacyDocument->file_path, $legacyPdf);
        $legacyDocument->update([
            'file_hash' => hash('sha256', $legacyPdf),
            'qr_code_generated_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->from(route('citizen.applications.show', $application))
            ->get(route('citizen.applications.print', $application));

        $response->assertRedirect(route('citizen.applications.show', $application))
            ->assertSessionHas('error', 'This certificate cannot be printed because its stored PDF or QR code is unavailable. Please contact the barangay office for assistance.');

        $this->assertDatabaseCount('issued_documents', 1);
        $this->assertNull($legacyDocument->fresh()->revoked_at);
        $this->assertNull($legacyDocument->fresh()->qr_code_generated_at);
        $this->assertSame(0, $application->fresh()->print_count);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_print_blocked',
            'subject_type' => ServiceRequest::class,
            'subject_id' => (string) $application->id,
        ]);
    }

    public function test_printing_a_document_with_a_missing_pdf_is_blocked_and_audited(): void
    {
        Storage::fake('supabase');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261106-0008',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $document = app(DocumentIssuer::class)->issue($application);
        Storage::disk('supabase')->delete($document->file_path);

        $this->actingAs($user)
            ->from(route('citizen.applications.show', $application))
            ->get(route('citizen.applications.print', $application))
            ->assertRedirect(route('citizen.applications.show', $application))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('issued_documents', 1);
        $this->assertNull($document->fresh()->revoked_at);
        $this->assertSame(0, $application->fresh()->print_count);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_print_blocked',
            'subject_type' => ServiceRequest::class,
            'subject_id' => (string) $application->id,
        ]);
    }

    public function test_issuing_the_same_application_twice_does_not_create_duplicate_documents(): void
    {
        Storage::fake('supabase');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0002',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $issuer = app(DocumentIssuer::class);

        $firstDocument = $issuer->issue($application);

        $this->assertDatabaseCount('issued_documents', 1);

        $secondDocument = $issuer->issue($application);

        $this->assertSame(
            $firstDocument->id,
            $secondDocument->id
        );

        $this->assertDatabaseCount('issued_documents', 1);
    }

    public function test_failed_pdf_upload_does_not_approve_application_or_create_document(): void
    {
        $disk = \Mockery::mock(FilesystemAdapter::class);

        $disk->shouldReceive('put')
            ->once()
            ->withArgs(
                fn ($path, $content) => str_starts_with($path, 'issued-documents/')
                    && str_ends_with($path, '.pdf')
                    && str_starts_with($content, '%PDF')
            )
            ->andReturn(false);

        $disk->shouldReceive('delete')
            ->once()
            ->withArgs(
                fn ($path) => str_starts_with($path, 'issued-documents/')
                    && str_ends_with($path, '.pdf')
            )
            ->andReturn(true);

        Storage::shouldReceive('disk')
            ->with('supabase')
            ->andReturn($disk);

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0003',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        try {
            app(DocumentIssuer::class)->issue($application);

            $this->fail('The issuance should fail when PDF storage fails.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'The PDF could not be stored.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'status' => ServiceRequest::PENDING,
        ]);

        $this->assertDatabaseCount('issued_documents', 0);
    }

    public function test_approved_application_can_be_reissued_with_a_new_document_version(): void
    {
        Storage::fake('supabase');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0004',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $issuer = app(DocumentIssuer::class);

        // Generate the original document.
        $originalDocument = $issuer->issue($application);

        // Reissue the approved application.
        $newDocument = $issuer->reissue($application);

        $this->assertNotSame(
            $originalDocument->document_uuid,
            $newDocument->document_uuid
        );

        $this->assertSame(
            $originalDocument->document_number,
            $newDocument->document_number
        );

        $this->assertSame(2, $newDocument->version);

        $this->assertNotNull($originalDocument->fresh()->revoked_at);
        $this->assertNull($newDocument->fresh()->revoked_at);

        // Both PDF files must remain stored.
        $this->assertTrue(
            Storage::disk('supabase')->exists($originalDocument->file_path)
        );

        $this->assertTrue(
            Storage::disk('supabase')->exists($newDocument->file_path)
        );

        $newPdfContent = Storage::disk('supabase')->get(
            $newDocument->file_path
        );

        $this->assertStringStartsWith('%PDF', $newPdfContent);

        $this->assertSame(
            hash('sha256', $newPdfContent),
            $newDocument->file_hash
        );

        $this->assertDatabaseHas('issued_documents', [
            'id' => $originalDocument->id,
            'version' => 1,
        ]);

        $this->assertDatabaseHas('issued_documents', [
            'id' => $newDocument->id,
            'version' => 2,
            'revoked_at' => null,
        ]);
        $revocationAudit = AuditLog::query()
            ->where('subject_type', IssuedDocument::class)
            ->where('subject_id', (string) $originalDocument->id)
            ->where('action', 'issued_documents.updated')
            ->firstOrFail();
        $this->assertNull($revocationAudit->old_values['revoked_at']);
        $this->assertNotNull($revocationAudit->new_values['revoked_at']);
        $creationAudit = AuditLog::query()
            ->where('subject_type', IssuedDocument::class)
            ->where('subject_id', (string) $newDocument->id)
            ->where('action', 'issued_documents.created')
            ->firstOrFail();
        $this->assertSame(2, $creationAudit->new_values['version']);

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'status' => ServiceRequest::APPROVED,
            'permit_number' => $originalDocument->document_number,
        ]);

        $this->assertDatabaseCount('issued_documents', 2);
    }

    public function test_failed_reissuance_keeps_the_previous_document_valid(): void
    {
        Storage::fake('supabase');

        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0005',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Barangay San Luis, Antipolo City',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $issuer = app(DocumentIssuer::class);

        // Issue the original document successfully.
        $originalDocument = $issuer->issue($application);

        $originalPath = $originalDocument->file_path;

        // Simulate a failed upload for the replacement PDF.
        $disk = \Mockery::mock(FilesystemAdapter::class);

        $disk->shouldReceive('put')
            ->once()
            ->andReturn(false);

        $disk->shouldReceive('delete')
            ->once()
            ->withArgs(
                fn ($path) => str_starts_with($path, 'issued-documents/')
                    && str_ends_with($path, '.pdf')
                    && $path !== $originalPath
            )
            ->andReturn(true);

        Storage::shouldReceive('disk')
            ->with('supabase')
            ->andReturn($disk);

        try {
            $issuer->reissue($application);

            $this->fail('Reissuance should fail when PDF storage fails.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'The replacement PDF could not be stored.',
                $exception->getMessage()
            );
        }

        // The original document must remain valid.
        $this->assertNull($originalDocument->fresh()->revoked_at);

        $this->assertDatabaseCount('issued_documents', 1);

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'status' => ServiceRequest::APPROVED,
        ]);

        // No new document should have replaced the original.
        $this->assertTrue(
            $originalDocument->fresh()->isValid()
        );
    }
}
