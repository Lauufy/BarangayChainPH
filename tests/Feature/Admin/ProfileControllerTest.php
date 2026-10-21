<?php

namespace Tests\Feature\Admin;

use App\Models\EmployeeProfile;
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
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        EmployeeProfile::create([
            'user_id' => $admin->id,
            'employee_id' => 'BSLCNPH-000001',
            'department' => 'Administration',
            'position' => 'Administrator',
        ]);

        Storage::fake('supabase');
        Storage::fake('local');
        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->andReturnUsing(
                fn (string $path, DateTimeInterface $expiration): string => 'https://storage.example.test/'.$path
            );

        $response = $this->actingAs($admin)
            ->post(route('admin.profile.photo'), [
                'photo' => UploadedFile::fake()->image('profile.png'),
            ]);

        $response->assertRedirect(route('admin.profile'));

        $photoPath = $admin->fresh()->employeeProfile->photo_path;
        $this->assertNotEmpty($photoPath);
        Storage::disk('supabase')->assertExists($photoPath);
        Storage::disk('local')->assertMissing($photoPath);

        $this->actingAs($admin)
            ->get(route('admin.profile.photo.show'))
            ->assertRedirect('https://storage.example.test/'.$photoPath);
    }
}
