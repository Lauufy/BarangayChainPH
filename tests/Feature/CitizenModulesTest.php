<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CitizenModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_render_each_portal_module(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]));

        foreach ($this->citizenModulePages() as $routeName => $viewName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertViewIs($viewName);
        }
    }

    public function test_guest_is_redirected_to_login_from_each_portal_module(): void
    {
        foreach (array_keys($this->citizenModulePages()) as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }
    }

    public function test_non_citizen_is_forbidden_from_each_portal_module(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]));

        foreach (array_keys($this->citizenModulePages()) as $routeName) {
            $this->get(route($routeName))
                ->assertForbidden();
        }
    }

    public function test_citizen_can_open_each_service_request_page_from_services(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]));

        $services = [
            'citizen.barangay-clearance' => 'Barangay clearance',
            'citizen.certificate-of-residency' => 'Certificate of residency',
            'citizen.certificate-of-indigency' => 'Certificate of indigency',
        ];

        $servicesPage = $this->get(route('citizen.services'))
            ->assertOk();

        foreach ($services as $routeName => $title) {
            $servicesPage->assertSee(route($routeName), false);

            $this->get(route($routeName))
                ->assertOk()
                ->assertViewIs('citizen.service-request')
                ->assertViewHas('title', $title);
        }
    }

    public function test_certificate_of_indigency_prefills_citizen_account_information(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
            'name' => 'Juan Dela Cruz',
        ]);
        $citizen->citizenProfile()->create([
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'address' => '123 Main Street, Barangay San Luis, Antipolo City, Rizal, 1870',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.certificate-of-indigency'))
            ->assertOk()
            ->assertViewIs('citizen.service-request')
            ->assertSee('value="Juan Dela Cruz"', false)
            ->assertSee('value="123 Main Street, Barangay San Luis, Antipolo City, Rizal, 1870"', false)
            ->assertSee('name="age"', false)
            ->assertSee('name="purpose"', false)
            ->assertDontSee('name="name" readonly', false)
            ->assertDontSee('name="age" readonly', false)
            ->assertDontSee('name="address" readonly', false);
    }

    public function test_certificate_of_indigency_can_be_previewed_modified_and_proceeded(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $this->actingAs($citizen);

        $data = [
            'name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
        ];

        $this->post(route('citizen.certificate-of-indigency.preview'), $data)
            ->assertOk()
            ->assertViewIs('citizen.service-request-preview')
            ->assertSee('Medical assistance');

        $this->post(route('citizen.certificate-of-indigency.modify'))
            ->assertRedirect(route('citizen.certificate-of-indigency'))
            ->assertSessionHasInput($data);

        $this->post(route('citizen.certificate-of-indigency.preview'), $data);

        $this->post(route('citizen.certificate-of-indigency.proceed'))
            ->assertRedirect(route('citizen.applications'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('service_requests', [
            'user_id' => $citizen->id,
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'status' => ServiceRequest::PENDING,
        ]);
    }

    public function test_barangay_clearance_can_be_previewed_modified_and_proceeded(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $this->actingAs($citizen);

        $data = [
            'name' => 'Maria Dela Cruz',
            'address' => '456 Mabini Street',
            'age' => 28,
            'purpose' => 'Employment requirement',
        ];

        $this->post(route('citizen.barangay-clearance.preview'), $data)
            ->assertOk()
            ->assertViewIs('citizen.service-request-preview')
            ->assertSee('Barangay Clearance')
            ->assertSee('Employment requirement');

        $this->post(route('citizen.barangay-clearance.modify'))
            ->assertRedirect(route('citizen.barangay-clearance'))
            ->assertSessionHasInput($data);

        $this->post(route('citizen.barangay-clearance.preview'), $data);

        $this->post(route('citizen.barangay-clearance.proceed'))
            ->assertRedirect(route('citizen.applications'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('service_requests', [
            'user_id' => $citizen->id,
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Maria Dela Cruz',
            'status' => ServiceRequest::PENDING,
        ]);
    }

    public function test_certificate_of_residency_can_be_previewed_modified_and_proceeded(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $this->actingAs($citizen);

        $data = [
            'name' => 'Ana Dela Cruz',
            'address' => '789 Rizal Avenue',
            'age' => 35,
            'purpose' => 'School requirement',
        ];

        $this->get(route('citizen.certificate-of-residency'))
            ->assertOk()
            ->assertSee('name="purpose"', false)
            ->assertSee('Certificate of residency');

        $this->post(route('citizen.certificate-of-residency.preview'), $data)
            ->assertOk()
            ->assertViewIs('citizen.service-request-preview')
            ->assertSee('Certificate of Residency')
            ->assertSee('School requirement');

        $this->post(route('citizen.certificate-of-residency.modify'))
            ->assertRedirect(route('citizen.certificate-of-residency'))
            ->assertSessionHasInput($data);

        $this->post(route('citizen.certificate-of-residency.preview'), $data);

        $this->post(route('citizen.certificate-of-residency.proceed'))
            ->assertRedirect(route('citizen.applications'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('service_requests', [
            'user_id' => $citizen->id,
            'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'applicant_name' => 'Ana Dela Cruz',
            'status' => ServiceRequest::PENDING,
        ]);
    }

    public function test_citizen_can_review_their_application_from_my_applications(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140003',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.applications'))
            ->assertSee(route('citizen.applications.show', $application), false)
            ->assertSee('Review')
            ->assertSee('Cancel')
            ->assertSee(route('citizen.applications.cancel', $application), false);

        $this->get(route('citizen.applications.show', $application))
            ->assertOk()
            ->assertViewIs('citizen.application-review')
            ->assertDontSee('Application approved')
            ->assertSee($application->reference_number)
            ->assertSee($application->purpose);
    }

    public function test_citizen_can_cancel_a_pending_application_within_two_hours(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140004',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($citizen)
            ->post(route('citizen.applications.cancel', $application), [
                'cancellation_reason' => 'No longer needed',
            ])
            ->assertRedirect(route('citizen.applications.show', $application));

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'status' => ServiceRequest::CANCELLED,
            'cancellation_reason' => 'No longer needed',
        ]);
    }

    public function test_citizen_can_see_the_rejection_reason_when_reviewing_a_rejected_application(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140007',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::REJECTED,
            'rejection_reason' => 'The submitted address could not be verified.',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.applications.show', $application))
            ->assertOk()
            ->assertSee('Reason for rejection')
            ->assertSee('The submitted address could not be verified.');
    }

    public function test_citizen_cannot_cancel_an_application_after_two_hours(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140005',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::PENDING,
        ]);
        $application->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->actingAs($citizen)
            ->post(route('citizen.applications.cancel', $application), [
                'cancellation_reason' => 'No longer needed',
            ])
            ->assertRedirect(route('citizen.applications.show', $application))
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'status' => ServiceRequest::PENDING,
        ]);
    }

    public function test_citizen_can_see_submitted_request_in_my_applications(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140002',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.applications'))
            ->assertOk()
            ->assertViewIs('citizen.applications')
            ->assertSee('BRGY-202610140002')
            ->assertSee('Certificate of indigency')
            ->assertSee('pending');
    }

    public function test_citizen_can_print_an_approved_application(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-202610140006',
            'service_type' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'applicant_name' => 'Juan Dela Cruz',
            'address' => 'Updated address',
            'age' => 30,
            'purpose' => 'Medical assistance',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261014-ABC12345',
        ]);
        $citizen->notify(new ServiceRequestApproved($application));

        $this->actingAs($citizen)
            ->get(route('citizen.applications'))
            ->assertSee('Print')
            ->assertSee(route('citizen.applications.print', $application), false);

        $this->actingAs($citizen)
            ->get(route('citizen.applications.print', $application))
            ->assertOk()
            ->assertViewIs('citizen.application-print')
            ->assertDontSee('Application approved')
            ->assertSee('Certificate of Indigency')
            ->assertSee('Medical assistance')
            ->assertSee('PERMIT-20261014-ABC12345')
            ->assertSee('Scan to verify authenticity');

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'print_count' => 1,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.applications.print', $application))
            ->assertOk();

        $this->assertDatabaseHas('service_requests', [
            'id' => $application->id,
            'print_count' => 2,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.applications.print', $application))
            ->assertForbidden();

        $verificationUrl = URL::signedRoute('permits.verify', [
            'serviceRequest' => $application,
        ]);

        $this->get($verificationUrl)
            ->assertOk()
            ->assertViewIs('permits.verify')
            ->assertSee('Genuine permit')
            ->assertSee('PERMIT-20261014-ABC12345');

        $this->get($verificationUrl.'&permit_number=counterfeit')
            ->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function citizenModulePages(): array
    {
        return [
            'citizen.dashboard' => 'citizen.dashboard',
            'citizen.services' => 'citizen.services',
            'citizen.documents' => 'citizen.documents',
            'citizen.applications' => 'citizen.applications',
            'citizen.profile' => 'citizen.profile',
            'citizen.identity-verification' => 'citizen.identity-verification',
        ];
    }
}
