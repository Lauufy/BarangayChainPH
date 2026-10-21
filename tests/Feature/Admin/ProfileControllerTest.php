<?php

namespace Tests\Feature\Admin;

use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_profile_photo_is_saved_and_served_from_the_configured_local_disk(): void
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

        $storageRoot = storage_path('framework/testing/render-profile-photos-'.Str::uuid());
        File::ensureDirectoryExists($storageRoot);
        config(['filesystems.disks.local.root' => $storageRoot]);

        try {
            $response = $this->actingAs($admin)
                ->post(route('admin.profile.photo'), [
                    'photo' => UploadedFile::fake()->image('profile.png'),
                ]);

            $response->assertRedirect(route('admin.profile'));

            $photoPath = $admin->fresh()->employeeProfile->photo_path;
            $this->assertTrue(Storage::disk('local')->exists($photoPath));

            $this->actingAs($admin)
                ->get(route('admin.profile.photo.show'))
                ->assertOk()
                ->assertHeader('content-type', 'image/png');
        } finally {
            File::deleteDirectory($storageRoot);
        }
    }
}
