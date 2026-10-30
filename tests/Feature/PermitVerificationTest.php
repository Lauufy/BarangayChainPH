<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PermitVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_qr_url_displays_valid_permit_with_masked_applicant_name(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0001',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-ABCDEFGH',
        ]);

        $url = URL::signedRoute('permits.verify', [
            'serviceRequest' => $application,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertViewIs('permits.verify')
            ->assertSee('Valid document')
            ->assertSee('Barangay Clearance')
            ->assertSee('Carlo M.')
            ->assertSee('PERMIT-20261030-ABCDEFGH')
            ->assertDontSee('Carlo Mamaril')
            ->assertDontSee('Private address')
            ->assertDontSee('Employment');
    }

    public function test_signed_qr_url_does_not_show_personal_details_for_unapproved_application(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0002',
            'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $url = URL::signedRoute('permits.verify', [
            'serviceRequest' => $application,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Invalid or unapproved document')
            ->assertDontSee('Carlo M.')
            ->assertDontSee('Carlo Mamaril')
            ->assertDontSee('Private address')
            ->assertDontSee('BRGY-20261030-0002');
    }

    public function test_unsigned_or_modified_qr_url_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $application = ServiceRequest::create([
            'user_id' => $user->id,
            'reference_number' => 'BRGY-20261030-0003',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Carlo Mamaril',
            'address' => 'Private address',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-20261030-IJKLMNOP',
        ]);

        $this->get(route('permits.verify', ['serviceRequest' => $application]))
            ->assertForbidden();
    }
}
