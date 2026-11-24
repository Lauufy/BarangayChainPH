<?php

namespace Tests\Feature\Citizen;

use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\DataErasureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_schema_keeps_only_the_back_storage_key(): void
    {
        $this->assertTrue(Schema::hasColumn('identity_verifications', 'document_back_key'));
        $this->assertFalse(Schema::hasColumn('identity_verifications', 'document_original_name'));
        $this->assertFalse(Schema::hasColumn('identity_verifications', 'document_back_path'));
        $this->assertFalse(Schema::hasColumn('identity_verifications', 'document_back_original_name'));
        $this->assertFalse(Schema::hasColumn('identity_verifications', 'document_validation_requires_review'));
    }

    public function test_erasing_a_citizen_removes_all_identity_files_from_private_storage(): void
    {
        Storage::fake('supabase');
        $user = User::factory()->create(['role' => 'citizen']);
        $paths = [
            "identity-documents/{$user->id}/front.jpg",
            "identity-documents/{$user->id}/back.jpg",
            "identity-selfies/{$user->id}/selfie.jpg",
        ];
        foreach ($paths as $path) {
            Storage::disk('supabase')->put($path, 'private image');
        }

        $user->identityVerifications()->create([
            'status' => IdentityVerification::VERIFIED,
            'document_type' => 'Philippine National ID',
            'document_path' => $paths[0],
            'document_back_key' => $paths[1],
            'selfie_path' => $paths[2],
        ]);

        $result = app(DataErasureService::class)->erase($user);

        $this->assertSame(3, $result['files']);
        foreach ($paths as $path) {
            Storage::disk('supabase')->assertMissing($path);
        }
        $this->assertDatabaseMissing('identity_verifications', ['user_id' => $user->id]);
    }
}
