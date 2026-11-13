<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\IdentityVerification;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Covers automatic audit events, actor context, and sensitive-value redaction. */
class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_and_delete_events_are_recorded_with_only_changed_fields(): void
    {
        $serviceRequest = $this->createServiceRequest();

        $serviceRequest->update(['purpose' => 'Housing']);
        $serviceRequest->update(['status' => ServiceRequest::PROCESSING]);
        $serviceRequest->delete();

        $logs = AuditLog::query()
            ->where('subject_type', ServiceRequest::class)
            ->where('subject_id', (string) $serviceRequest->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $logs);
        $this->assertSame('service_requests.created', $logs[0]->action);
        $this->assertSame('service_requests.updated', $logs[1]->action);
        $this->assertSame(['purpose' => 'Employment'], $logs[1]->old_values);
        $this->assertSame(['purpose' => 'Housing'], $logs[1]->new_values);
        $this->assertSame('status_changed', $logs[2]->action);
        $this->assertSame(['status' => ServiceRequest::PENDING], $logs[2]->old_values);
        $this->assertSame(['status' => ServiceRequest::PROCESSING], $logs[2]->new_values);
        $this->assertSame('service_requests.deleted', $logs[3]->action);
        $this->assertSame(ServiceRequest::PROCESSING, $logs[3]->old_values['status']);
    }

    public function test_password_id_hash_paths_urls_and_base64_are_redacted(): void
    {
        $user = User::factory()->create(['password' => 'SensitivePass123!']);

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'status' => IdentityVerification::PENDING,
            'document_type' => 'National ID',
            'document_path' => 'private/id-card.pdf',
            'document_original_name' => 'national-id.pdf',
            'id_number_hash' => 'sensitive-id-number-hash',
        ]);

        $log = app(AuditLogger::class)->log(
            'manual.redaction_test',
            $verification,
            newValues: [
                'signed_url' => 'https://storage.example/file?token=secret',
                'blob' => base64_encode(str_repeat('uploaded-file-content', 20)),
                'id_number' => '1234567890',
            ]
        );

        $userLog = AuditLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', (string) $user->id)
            ->where('action', 'users.created')
            ->firstOrFail();
        $verificationLog = AuditLog::query()
            ->where('subject_type', IdentityVerification::class)
            ->where('subject_id', (string) $verification->id)
            ->where('action', 'identity_verifications.created')
            ->firstOrFail();

        $this->assertSame('[redacted]', $userLog->new_values['password']);
        $this->assertSame('[redacted]', $verificationLog->new_values['id_number_hash']);
        $this->assertSame('[redacted]', $verificationLog->new_values['document_path']);
        $this->assertSame('[redacted]', $verificationLog->new_values['document_original_name']);
        $this->assertSame('[redacted]', $log->new_values['signed_url']);
        $this->assertSame('[redacted]', $log->new_values['blob']);
        $this->assertSame('[redacted]', $log->new_values['id_number']);
    }

    public function test_staff_permission_update_records_actor_role_and_request_metadata(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => []]);

        $this->actingAs($admin)
            ->withServerVariables([
                'REMOTE_ADDR' => '192.0.2.10',
                'HTTP_USER_AGENT' => 'Audit feature test',
            ])
            ->put(route('admin.users.permissions.update', $staff), [
                'permissions' => ['reports.view'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $log = AuditLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', (string) $staff->id)
            ->where('action', 'role_changed')
            ->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('admin', $log->user_role);
        $this->assertSame('192.0.2.10', $log->ip_address);
        $this->assertSame('Audit feature test', $log->user_agent);
        $this->assertSame(['permissions' => []], $log->old_values);
        $this->assertSame(['permissions' => ['reports.view']], $log->new_values);
    }

    public function test_background_model_change_records_a_null_actor(): void
    {
        $serviceRequest = $this->createServiceRequest();
        Auth::logout();

        app(AuditLogger::class)->withinBackgroundExecution(
            fn() => $serviceRequest->update(['status' => ServiceRequest::PROCESSING])
        );

        $log = AuditLog::query()
            ->where('subject_id', (string) $serviceRequest->id)
            ->where('action', 'status_changed')
            ->firstOrFail();

        $this->assertNull($log->user_id);
        $this->assertSame(ServiceRequest::PROCESSING, $log->new_values['status']);
    }

    public function test_explicit_status_change_helper_records_a_note_without_an_actor(): void
    {
        $serviceRequest = $this->createServiceRequest();

        $log = app(AuditLogger::class)->logStatusChange(
            $serviceRequest,
            ServiceRequest::PENDING,
            ServiceRequest::REJECTED,
            'Missing supporting details'
        );

        $this->assertSame('status_changed', $log->action);
        $this->assertNull($log->user_id);
        $this->assertSame(['status' => ServiceRequest::PENDING], $log->old_values);
        $this->assertSame([
            'status' => ServiceRequest::REJECTED,
            'note' => 'Missing supporting details',
        ], $log->new_values);
    }

    public function test_login_and_logout_events_are_audited(): void
    {
        $admin = User::factory()->create([
            'email' => 'audit-admin@example.com',
            'password' => 'StrongPass123!',
            'role' => 'admin',
            'two_factor_enabled' => false,
        ]);

        $this->post(route('login.store'), [
            'identifier' => $admin->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'user_role' => 'admin',
            'action' => 'login',
            'subject_type' => User::class,
            'subject_id' => (string) $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'user_role' => 'admin',
            'action' => 'logout',
            'subject_type' => User::class,
            'subject_id' => (string) $admin->id,
        ]);
    }

    public function test_staff_approval_records_the_status_change(): void
    {
        Storage::fake('supabase');

        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        $serviceRequest = $this->createServiceRequest();

        $this->actingAs($staff)
            ->post(route('admin.permits.approve', $serviceRequest), [
                'expires_at' => now()->addMonths(6)->toDateString(),
            ])
            ->assertRedirect(route('admin.permits.show', $serviceRequest));

        $log = AuditLog::query()
            ->where('subject_type', ServiceRequest::class)
            ->where('subject_id', (string) $serviceRequest->id)
            ->where('action', 'status_changed')
            ->firstOrFail();

        $this->assertSame($staff->id, $log->user_id);
        $this->assertSame(ServiceRequest::PENDING, $log->old_values['status']);
        $this->assertSame(ServiceRequest::APPROVED, $log->new_values['status']);
    }

    public function test_staff_rejection_records_the_status_change_and_note(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        $serviceRequest = $this->createServiceRequest();

        $this->actingAs($staff)
            ->post(route('admin.permits.reject', $serviceRequest), [
                'rejection_reason' => 'Missing supporting details',
            ])
            ->assertRedirect(route('admin.permits.show', $serviceRequest));

        $log = AuditLog::query()
            ->where('subject_type', ServiceRequest::class)
            ->where('subject_id', (string) $serviceRequest->id)
            ->where('action', 'status_changed')
            ->firstOrFail();

        $this->assertSame(ServiceRequest::PENDING, $log->old_values['status']);
        $this->assertSame(ServiceRequest::REJECTED, $log->new_values['status']);
        $this->assertSame('Missing supporting details', $log->new_values['rejection_reason']);
    }

    public function test_audit_log_cannot_be_updated(): void
    {
        $log = $this->createAuditLog();

        $this->expectException(\LogicException::class);
        $log->update(['action' => 'changed']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = $this->createAuditLog();

        $this->expectException(\LogicException::class);
        $log->delete();
    }

    public function test_audit_row_is_preserved_when_its_user_is_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $log = app(AuditLogger::class)->log('manual', $admin, actor: $admin);

        $admin->delete();

        $this->assertModelExists($log->fresh());
        $this->assertNull($log->fresh()->user_id);
        $this->assertSame('admin', $log->fresh()->user_role);
    }

    public function test_identity_verification_changes_are_audited(): void
    {
        $user = User::factory()->create();
        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'status' => IdentityVerification::PENDING,
            'document_type' => 'Passport',
            'document_path' => 'private/passport.pdf',
        ]);

        $verification->update(['status' => IdentityVerification::VERIFIED]);

        $log = AuditLog::query()
            ->where('subject_type', IdentityVerification::class)
            ->where('subject_id', (string) $verification->id)
            ->where('action', 'status_changed')
            ->firstOrFail();

        $this->assertSame(IdentityVerification::PENDING, $log->old_values['status']);
        $this->assertSame(IdentityVerification::VERIFIED, $log->new_values['status']);
    }

    private function createServiceRequest(): ServiceRequest
    {
        return ServiceRequest::create([
            'user_id' => User::factory()->create(['role' => 'citizen'])->id,
            'reference_number' => 'BRGY-20261106-' . fake()->unique()->numerify('####'),
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Jamie Citizen',
            'address' => 'Barangay San Luis',
            'age' => 28,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);
    }

    private function createAuditLog(): AuditLog
    {
        return AuditLog::create([
            'action' => 'test',
            'subject_type' => User::class,
            'subject_id' => '123',
            'created_at' => now(),
        ]);
    }
}
