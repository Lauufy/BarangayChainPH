<?php

namespace Tests\Feature\Verification;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function issuePermit(string $number, string $name): void
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

        IssuedDocument::create([
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
        $this->issuePermit('PERMIT-AUTH-1', 'Maria Santos');

        $this->actingAs($this->staff())
            ->get(route('admin.document-authentication.index'))
            ->assertOk()
            ->assertSee('PERMIT-AUTH-1')
            ->assertSee('Maria Santos')
            ->assertSee('Barangay Clearance')
            ->assertSee('Active');
    }

    public function test_search_filters_by_applicant_name(): void
    {
        $this->issuePermit('PERMIT-AUTH-1', 'Maria Santos');
        $this->issuePermit('PERMIT-AUTH-2', 'Pedro Reyes');

        $this->actingAs($this->staff())
            ->get(route('admin.document-authentication.index', ['search' => 'Santos']))
            ->assertOk()
            ->assertSee('PERMIT-AUTH-1')
            ->assertDontSee('PERMIT-AUTH-2');
    }
}