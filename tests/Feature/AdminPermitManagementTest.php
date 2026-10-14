<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestApproved;
use App\Notifications\ServiceRequestRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminPermitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_submitted_indigency_requests_in_permit_management(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $request = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140001',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.permits.index'))
            ->assertOk()
            ->assertViewIs('admin.permits')
            ->assertSee('BRGY-202610140001')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Certificate of indigency')
            ->assertSee('Review')
            ->assertSee(route('admin.permits.show', $request), false);

        $this->actingAs($admin)
            ->get(route('admin.permits.show', $request))
            ->assertOk()
            ->assertViewIs('admin.permit-review')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Medical assistance')
            ->assertSee('Updated address')
            ->assertSee('Approve application')
            ->assertSee('Reject application');
    }

    public function test_admin_can_approve_a_pending_application(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $request = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140002',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Maria Santos',
            'address' => 'Main Street',
            'age' => 28,
            'purpose' => 'School assistance',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permits.approve', $request))
            ->assertRedirect(route('admin.permits.show', $request))
            ->assertSessionHas('success', 'The application was approved.');

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => ServiceRequest::APPROVED,
        ]);

        Notification::assertSentTo($citizen, ServiceRequestApproved::class, function (ServiceRequestApproved $notification) use ($request): bool {
            $mail = $notification->toMail($request->user);

            return $notification->serviceRequest->is($request)
                && $mail->actionText === 'View My Documents'
                && $mail->actionUrl === route('citizen.documents');
        });

        $this->assertInstanceOf(ShouldQueue::class, new ServiceRequestApproved($request));
        $this->assertNotNull($request->fresh()->permit_number);
        $this->assertSame(0, $request->fresh()->print_count);

        $this->actingAs($admin)
            ->get(route('admin.permits.show', $request))
            ->assertSee('Prints remaining')
            ->assertSee('2')
            ->assertSee('of 2');
    }

    public function test_admin_cannot_approve_a_cancelled_application(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $request = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140009',
            'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'applicant_name' => 'Ana Cruz',
            'address' => 'Rizal Street',
            'age' => 40,
            'purpose' => 'Employment',
            'status' => ServiceRequest::CANCELLED,
            'cancellation_reason' => 'No longer needed',
            'cancelled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permits.approve', $request))
            ->assertRedirect(route('admin.permits.show', $request))
            ->assertSessionHas('error', 'Only pending applications can be approved.');

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => ServiceRequest::CANCELLED,
            'permit_number' => null,
        ]);
        Notification::assertNothingSent();
    }

    public function test_admin_can_update_an_approved_permit_print_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $request = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140004',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Ana Cruz',
            'address' => 'Rizal Street',
            'age' => 40,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261014-TEST1234',
            'print_count' => 2,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.permits.print-limit.update', $request), [
                'print_limit' => 3,
            ])
            ->assertRedirect(route('admin.permits.show', $request))
            ->assertSessionHas('success', 'The permit print limit was updated.');

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'print_count' => 2,
            'print_limit' => 3,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.permits.show', $request))
            ->assertSee('Prints remaining')
            ->assertSee('1')
            ->assertSee('of 3')
            ->assertSee('Update print allowance');

        $this->actingAs($citizen)
            ->get(route('citizen.applications.print', $request))
            ->assertOk();

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'print_count' => 3,
            'print_limit' => 3,
        ]);
    }

    public function test_admin_can_reject_a_pending_application_with_a_reason(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $request = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140003',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Pedro Reyes',
            'address' => 'Market Road',
            'age' => 35,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permits.reject', $request), [
                'rejection_reason' => 'The submitted details need verification.',
            ])
            ->assertRedirect(route('admin.permits.show', $request))
            ->assertSessionHas('success', 'The application was rejected.');

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => ServiceRequest::REJECTED,
            'rejection_reason' => 'The submitted details need verification.',
        ]);

        Notification::assertSentTo($citizen, ServiceRequestRejected::class, function (ServiceRequestRejected $notification) use ($request): bool {
            $mail = $notification->toMail($request->user);

            return $notification->serviceRequest->is($request)
                && $mail->actionText === 'View My Applications'
                && $mail->actionUrl === route('citizen.applications')
                && in_array('Reason: The submitted details need verification.', $mail->introLines, true);
        });

        $this->assertInstanceOf(ShouldQueue::class, new ServiceRequestRejected($request));
    }
}
