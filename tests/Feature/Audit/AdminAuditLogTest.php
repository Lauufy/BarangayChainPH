<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Covers audit record creation and the admin audit-log view. */
class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** Check service request edits are stored and readable in the filtered log. */
    public function test_admin_can_filter_a_service_request_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->createServiceRequest();
        $this->actingAs($admin);

        $application->update(['status' => ServiceRequest::PROCESSING]);
        $updatedLog = AuditLog::query()->where('action', 'status_changed')->firstOrFail();

        $this->assertSame('pending', $updatedLog->old_values['status']);
        $this->assertSame('processing', $updatedLog->new_values['status']);
        $this->get(route('admin.audit-logs.index', [
            'user_id' => $admin->id,
            'action' => 'status_changed',
            'date' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Status Changed')
            ->assertSee('Status: pending -> processing');
    }

    public function test_audit_search_query_is_ignored_and_client_search_keeps_the_pager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(0, 10) as $index) {
            AuditLog::create([
                'user_id' => $admin->id,
                'user_role' => 'admin',
                'action' => 'needle.audit_event',
                'subject_type' => ServiceRequest::class,
                'subject_id' => (string) (100 + $index),
                'created_at' => now()->subMinutes($index),
            ]);
        }

        AuditLog::create([
            'user_id' => $admin->id,
            'user_role' => 'admin',
            'action' => 'unrelated.event',
            'subject_type' => ServiceRequest::class,
            'subject_id' => '999',
            'created_at' => now()->addMinute(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index', [
            'search' => 'needle',
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertSee('Needle Audit Event')
            ->assertSee('Unrelated Event')
            ->assertSee('data-table-key="audit-logs"', false)
            ->assertSee('placeholder="Search user, action, or record"', false)
            
            ->assertSee('aria-label="Pagination"', false)
            ->assertDontSee('name="search"', false)
            ->assertDontSee('search=')
            ->assertSee('Next page', false);
    }

    /** Check issued document records use the same audit observer. */
    public function test_issued_document_creation_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
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
