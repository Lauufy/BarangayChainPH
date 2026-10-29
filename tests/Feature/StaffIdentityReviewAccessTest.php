<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffIdentityReviewAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $permissions): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
            'permissions' => $permissions,
        ]);
    }

    public function test_staff_with_permission_can_open_identity_review(): void
    {
        $this->actingAs($this->staff(['identity.review']))
            ->get(route('admin.identity-verification.index'))
            ->assertOk();
    }

    public function test_staff_without_permission_cannot_open_identity_review(): void
    {
        $this->actingAs($this->staff([]))
            ->get(route('admin.identity-verification.index'))
            ->assertForbidden();
    }

    public function test_admin_can_still_access_identity_review(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.identity-verification.index'))
            ->assertOk();
    }
}