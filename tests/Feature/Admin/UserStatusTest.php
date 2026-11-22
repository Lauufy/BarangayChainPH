<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_suspend_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
                'status' => 'suspended',
                'employment_type' => 'full_time',
                'department' => 'Barangay Council',
                'position' => 'Punong Barangay',
            ])
            ->assertSessionHasErrorsIn('updateUser', 'status');

        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['role' => 'citizen', 'status' => 'suspended']);

        $this->post(route('login.store'), [
            'identifier' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }
}
