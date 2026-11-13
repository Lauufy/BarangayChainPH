<?php

namespace Tests\Feature\Citizen;

use App\Models\CitizenProfile;
use App\Models\User;
use App\Services\SupabaseStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Covers Supabase storage for citizen identity-verification photos. */
class IdentityVerificationPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_photos_are_saved_to_supabase_and_served_through_signed_urls(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        CitizenProfile::create([
            'user_id' => $citizen->id,
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
            'address' => '1 Main Street, San Luis',
            'barangay' => 'San Luis',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'place_of_birth' => 'San Luis',
            'city_municipality' => 'San Luis',
            'province' => 'Province',
            'zip_code' => '1234',
        ]);

        Storage::fake('supabase');
        Storage::fake('local');

        $response = $this->actingAs($citizen)
            ->post(route('citizen.identity-verification.document'), [
                'document_type' => 'Philippine National ID',
                'id_number' => '123456789',
                'document' => UploadedFile::fake()->image('id.jpg'),
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
                'selfie_source' => 'upload',
                'agree_truth' => '1',
                'agree_privacy' => '1',
            ]);

        $response->assertRedirect(route('citizen.identity-verification'));

        $verification = $citizen->identityVerifications()->latest()->firstOrFail();
        Storage::disk('supabase')->assertExists($verification->document_path);
        Storage::disk('supabase')->assertExists($verification->selfie_path);
        Storage::disk('local')->assertMissing($verification->document_path);
        Storage::disk('local')->assertMissing($verification->selfie_path);
        $this->assertStringNotContainsString('http', $verification->document_path);
        $this->assertStringNotContainsString('http', $verification->selfie_path);

        $admin = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['identity.review'],
        ]);

        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->twice()
            ->andReturn('https://storage.example/identity.jpg');

        $this->actingAs($citizen)
            ->get(route('citizen.identity-verification.file', $verification))
            ->assertRedirect('https://storage.example/identity.jpg');

        $this->actingAs($admin)
            ->get(route('admin.identity-verification.selfie', $verification))
            ->assertRedirect('https://storage.example/identity.jpg');
    }

    public function test_uploaded_pdf_identity_documents_are_stored_in_supabase(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        CitizenProfile::create([
            'user_id' => $citizen->id,
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
            'address' => '1 Main Street, San Luis',
            'barangay' => 'San Luis',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'place_of_birth' => 'San Luis',
            'city_municipality' => 'San Luis',
            'province' => 'Province',
            'zip_code' => '1234',
        ]);

        Storage::fake('supabase');
        Storage::fake('local');

        $response = $this->actingAs($citizen)
            ->post(route('citizen.identity-verification.document'), [
                'document_type' => 'Philippine National ID',
                'id_number' => '987654321',
                'document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
                'selfie_source' => 'upload',
                'agree_truth' => '1',
                'agree_privacy' => '1',
            ]);

        $response->assertRedirect(route('citizen.identity-verification'));

        $verification = $citizen->identityVerifications()->latest()->firstOrFail();
        Storage::disk('supabase')->assertExists($verification->document_path);
        Storage::disk('supabase')->assertExists($verification->selfie_path);
        Storage::disk('local')->assertMissing($verification->document_path);
        Storage::disk('local')->assertMissing($verification->selfie_path);
        $this->assertStringNotContainsString('http', $verification->document_path);
        $this->assertStringNotContainsString('http', $verification->selfie_path);

        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->once()
            ->andReturn('https://storage.example/identity.pdf');

        $this->actingAs($citizen)
            ->get(route('citizen.identity-verification.file', $verification))
            ->assertRedirect('https://storage.example/identity.pdf');
    }
}
