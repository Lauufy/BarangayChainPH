<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** Check service request edits are stored and readable in the filtered log. */
    public function test_admin_can_filter_a_service_request_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $application = $this->createServiceRequest();
        $this->actingAs($admin);

        $application->update(['status' => ServiceRequest::PROCESSING]);
        $updatedLog = AuditLog::query()->where('action', 'service_requests.updated')->firstOrFail();

        $this->assertSame('pending', $updatedLog->old_values['status']);
        $this->assertSame('processing', $updatedLog->new_values['status']);
        $this->get(route('admin.audit-logs.index', [
            'user_id' => $admin->id,
            'action' => 'service_requests.updated',
            'date' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Service request updated')
            ->assertSee('Status: pending -> processing')
            ->assertDontSee('No audit records match these filters.');
    }

    /** Check issued document records use the same audit observer. */
    public function test_issued_document_creation_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $application = $this->createServiceRequest();
        $this->actingAs($admin);

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => 'f77a90b9-7abf-4d4a-9b76-7f58597ce6f7',
            'document_number' => 'PERMIT-TEST-1',
            'disk' => 'local',
            'file_path' => 'issued-documents/permit.pdf',
            'file_hash' => str_repeat('a', 64),
            'issued_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'issued_documents.created',
            'subject_type' => IssuedDocument::class,
        ]);
    }

    /** Make the legacy request record used by audit tests. */
    private function createServiceRequest(): ServiceRequest
    {
        return ServiceRequest::create([
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'reference_number' => 'BRGY-20261105-0001',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Jamie Citizen',
            'address' => 'Barangay San Luis',
            'age' => 28,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);
    }
}
