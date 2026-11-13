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

    private function verifiedCitizen(string $suffix): User
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
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
            'agree_truth' => '1',
            'requirements' => [
                'valid_id' => UploadedFile::fake()->create('valid-id.pdf', 10, 'application/pdf'),
                'proof_of_residency' => UploadedFile::fake()->create('proof-of-residency.pdf', 10, 'application/pdf'),
                'cedula' => UploadedFile::fake()->create('cedula.pdf', 10, 'application/pdf'),
            ],
        ];
    }
}
