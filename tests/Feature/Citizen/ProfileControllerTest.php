<?php

namespace Tests\Feature\Citizen;

use App\Models\CitizenProfile;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\SupabaseStorage;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_profile_photo_is_saved_to_supabase_and_served_with_a_temporary_url(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
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
        Storage::fake('local');
        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->andReturnUsing(
                fn (string $path, DateTimeInterface $expiration): string => 'https://storage.example.test/'.$path
            );

        $response = $this->actingAs($citizen)
            ->post(route('citizen.profile.photo'), [
                'photo' => UploadedFile::fake()->image('profile.jpg'),
            ]);

        $response->assertRedirect(route('citizen.profile'));

        $photoPath = $citizen->fresh()->citizenProfile->photo_path;
        $this->assertNotEmpty($photoPath);
        $this->assertTrue(Storage::disk('supabase')->exists($photoPath));
        $this->assertFalse(Storage::disk('local')->exists($photoPath));

        $this->actingAs($citizen)
            ->get(route('citizen.profile.photo.show'))
            ->assertRedirect('https://storage.example.test/'.$photoPath);
    }
}
