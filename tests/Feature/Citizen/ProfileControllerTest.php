<?php

namespace Tests\Feature\Citizen;

use App\Models\CitizenProfile;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\SupabaseStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Covers citizen profile updates and Supabase photo storage. */
class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_profile_photo_is_saved_and_served_through_a_signed_url(): void
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
        ]);
        $citizen->identityVerifications()->create([
            'status' => IdentityVerification::VERIFIED,
            'document_type' => 'Philippine National ID',
            'document_path' => 'identity-documents/1/id.pdf',
        ]);

        Storage::fake('supabase');

        $response = $this->actingAs($citizen)
            ->post(route('citizen.profile.photo'), [
                'photo' => UploadedFile::fake()->image('profile.jpg'),
            ]);

        $response->assertRedirect(route('citizen.profile'));

        $photoPath = $citizen->fresh()->citizenProfile->photo_path;
        $this->assertNotEmpty($photoPath);
        $this->assertTrue(Storage::disk('supabase')->exists($photoPath));
        $this->assertStringNotContainsString('http', $photoPath);

        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->once()
            ->andReturn('https://storage.example/profile.jpg');

        $this->actingAs($citizen)
            ->get(route('citizen.profile.photo.show'))
            ->assertRedirect('https://storage.example/profile.jpg');
    }
}
