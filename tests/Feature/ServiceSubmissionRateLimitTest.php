<?php

namespace Tests\Feature;

use App\Models\CitizenProfile;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceSubmissionRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_submissions_are_limited_per_user_and_return_a_friendly_429(): void
    {
        Storage::fake('supabase');

        $citizen = $this->verifiedCitizen('first');
        $submissionRoute = route('citizen.barangay-clearance.store');

        $this->actingAs($citizen)
            ->post($submissionRoute, $this->validSubmission())
            ->assertRedirect(route('citizen.applications'))
            ->assertSessionHas('success');

        for ($attempt = 1; $attempt < 5; $attempt++) {
            $this->actingAs($citizen)
                ->post($submissionRoute, $this->validSubmission())
                ->assertRedirect(route('citizen.applications'));
        }

        $this->actingAs($citizen)
            ->post($submissionRoute, $this->validSubmission())
            ->assertTooManyRequests()
            ->assertSee('Too many service applications were submitted in a short time.');

        $anotherCitizen = $this->verifiedCitizen('second');

        $this->actingAs($anotherCitizen)
            ->post($submissionRoute, $this->validSubmission())
            ->assertRedirect(route('citizen.applications'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('service_requests', 2);
    }

    public function test_citizens_can_preview_each_reference_certificate(): void
    {
        $this->withoutVite();
        $this->actingAs($this->verifiedCitizen('preview'));

        foreach (array_keys(config('barangay_services.services')) as $slug) {
            $response = $this->get(route('citizen.'.$slug))->assertOk()
                ->assertSeeInOrder(['data-panel="1"', 'data-panel="2"', 'Preview your document', 'data-panel="3"', 'Review and confirmation'], false)
                ->assertDontSee('data-panel="4"', false);

            $response->assertSee('profile_email')->assertSee('profile_date_of_birth')
                ->assertDontSee('profile_status')->assertDontSee('profile_consent_ip');
            $this->assertSame(2, substr_count($response->getContent(), 'Personal Information'));

            if ($slug === 'certificate-of-indigency') {
                $response->assertDontSee('Supporting documents')->assertDontSee('type="file"', false);
            } else {
                $response->assertSee('Supporting documents')->assertSee('type="file"', false);
            }

            if (in_array($slug, ['business-clearance', 'barangay-clearance', 'certificate-of-indigency'], true)) {
                $response->assertSee('Draft certificate preview')->assertSee('header_permit_new.png');
            }
        }
    }

    public function test_business_clearance_checks_the_requirements_for_each_application_type(): void
    {
        Storage::fake('supabase');
        $this->actingAs($this->verifiedCitizen('business'));
        $url = route('citizen.business-clearance.store');
        $fields = [
            'business_name' => 'Sample Store',
            'business_address' => '12 Main Street',
            'business_owner_name' => 'Maria Reyes',
            'business_type' => 'Single proprietor',
            'nature_of_business' => 'Retailer',
            'business_contact_number' => '09171234567',
            'agree_truth' => '1',
            'requirements' => ['business_registration' => UploadedFile::fake()->create('dti.pdf', 10, 'application/pdf')],
        ];

        $this->post($url, $fields + ['application_type' => 'Renewal'])
            ->assertSessionHasErrors('requirements.old_clearance');
        $this->post($url, $fields + ['application_type' => 'New application'])
            ->assertSessionHasErrors('paid_up_capital');
        $this->post($url, $fields + ['application_type' => 'New application', 'paid_up_capital' => '12500.50'])
            ->assertRedirect(route('citizen.applications'))
            ->assertSessionHas('success');
    }

    public function test_indigency_accepts_a_specified_purpose_without_uploads(): void
    {
        $this->actingAs($this->verifiedCitizen('indigency'));
        $url = route('citizen.certificate-of-indigency.store');
        $fields = ['purpose' => 'Others', 'agree_truth' => '1'];

        $this->post($url, $fields)->assertSessionHasErrors('purpose_other');
        $this->post($url, $fields + ['purpose_other' => 'School supplies'])
            ->assertRedirect(route('citizen.applications'));
        $this->assertDatabaseHas('service_requests', ['purpose' => 'School supplies']);
    }

    public function test_cedula_validates_contact_number_and_saves_application(): void
    {
        Storage::fake('supabase');
        $this->actingAs($this->verifiedCitizen('cedula'));
        $url = route('citizen.cedula.store');
        $fields = [
            'occupation' => 'Teacher',
            'annual_income' => '350000.50',
            'agree_truth' => '1',
            'requirements' => ['valid_id' => UploadedFile::fake()->create('id.pdf', 10, 'application/pdf')],
        ];

        $this->post($url, $fields + ['contact_number' => 'invalid'])
            ->assertSessionHasErrors('contact_number');

        $this->get(route('citizen.cedula'))->assertOk()->assertSee('autocomplete="tel-national"', false);

        $this->post($url, $fields + ['contact_number' => '9171234567'])
            ->assertRedirect(route('citizen.applications'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('service_requests', [
            'service_type' => 'community_tax_certificate',
            'purpose' => 'Community tax certificate for Teacher',
            'contact_number' => '+639171234567',
        ]);
    }

    private function verifiedCitizen(string $suffix): User
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);

        CitizenProfile::create([
            'user_id' => $citizen->id,
            'date_of_birth' => '1990-01-01',
            'address' => '1 Main Street, San Luis',
            'barangay' => 'San Luis',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'gender' => 'Female',
            'place_of_birth' => 'San Luis',
            'city_municipality' => 'San Luis',
            'province' => 'Rizal',
            'zip_code' => '1234',
        ]);

        $citizen->identityVerifications()->create([
            'status' => IdentityVerification::VERIFIED,
            'document_type' => 'Philippine National ID',
            'document_path' => "identity-documents/{$suffix}/id.pdf",
        ]);

        return $citizen;
    }

    private function validSubmission(): array
    {
        return [
            'purpose' => 'Employment',
            'requirements' => ['valid_id' => UploadedFile::fake()->create('id.pdf', 10, 'application/pdf')],
            'resident_since' => 2015,
            'contact_number' => '09171234567',
            'id_presented' => 'SSS',
            'agree_truth' => '1',
        ];
    }
}
