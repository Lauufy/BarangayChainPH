<?php

namespace Tests\Feature\Verification;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDocumentAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'permissions' => ['documents.authenticate'],
        ]);
    }

    private function issuePermit(string $number, string $name): IssuedDocument
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-'.Str::upper(Str::random(12)),
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => $name,
            'address' => 'Barangay San Luis',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => $number,
        ]);

        return IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => (string) Str::uuid(),
            'document_number' => $number,
            'version' => 1,
            'disk' => 'local',
            'file_path' => 'issued-documents/test.pdf',
            'file_hash' => str_repeat('a', 64),
            'issued_at' => now(),
        ]);
    }

    public function test_page_lists_an_issued_document(): void
    {
        $document = $this->issuePermit('PERMIT-AUTH-1', 'Maria Santos');

        $this->actingAs($this->staff())
            ->get(route('admin.document-authentication.index'))
            ->assertOk()
            ->assertSee(route('admin.document-authentication.show', $document))
            ->assertSee('PERMIT-AUTH-1')
            ->assertSee('Maria Santos')
            ->assertSee('Barangay Clearance')
            ->assertSee('Active');
    }

    public function test_authorized_reviewer_can_view_the_selected_version_inline(): void
    {
        $disk = Storage::fake('local');
        $document = $this->issuePermit('PERMIT-AUTH-1', 'Maria Santos');
        $pdf = '%PDF-1.4 issued version one';
        $disk->put($document->file_path, $pdf);
        $document->update(['file_hash' => hash('sha256', $pdf), 'revoked_at' => now()]);
        $url = route('admin.document-authentication.show', $document);

        $this->actingAs($this->staff())->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="PERMIT-AUTH-1-v1.pdf"')
            ->assertContent($pdf);

        $disk->put($document->file_path, 'altered');
        $this->get($url)->assertStatus(409);

        $disk->delete($document->file_path);
        $this->get($url)->assertNotFound();
    }

    public function test_staff_without_document_permission_cannot_open_a_preview(): void
    {
        $document = $this->issuePermit('PERMIT-AUTH-1', 'Maria Santos');
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => []]);

        $this->actingAs($staff)->get(route('admin.document-authentication.show', $document))
            ->assertForbidden();
    }

    public function test_search_query_is_ignored_and_client_search_toolbar_is_rendered(): void
    {
        $this->issuePermit('PERMIT-AUTH-1', 'Maria Santos');
        $this->issuePermit('PERMIT-AUTH-2', 'Pedro Reyes');

        $this->actingAs($this->staff())
            ->get(route('admin.document-authentication.index', ['search' => 'not-in-record']))
            ->assertOk()
            ->assertSee('PERMIT-AUTH-1')
            ->assertSee('PERMIT-AUTH-2')
            ->assertSee('data-table-key="document-authentication"', false)
            ->assertSee('placeholder="Search by permit number or name"', false)
            ;
    }
}
