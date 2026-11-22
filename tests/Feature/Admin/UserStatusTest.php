<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_suspend_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin), ['status' => 'suspended'])
            ->assertSessionHasErrorsIn('updateUserStatus', 'status');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }

    public function test_staff_cannot_change_an_account_status(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $citizen = User::factory()->create(['role' => 'citizen', 'status' => 'active']);

        $this->actingAs($staff)
            ->patch(route('admin.users.status', $citizen), ['status' => 'suspended'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $citizen->id, 'status' => 'active']);
    }

    #[DataProvider('accountStatuses')]
    public function test_admin_can_change_another_users_status(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen', 'status' => 'active']);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $citizen), ['status' => $status])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'The account status was updated successfully.');

        $this->assertDatabaseHas('users', ['id' => $citizen->id, 'status' => $status]);
    }

    public static function accountStatuses(): array
    {
        return [
            'active' => ['active'],
            'inactive' => ['inactive'],
            'suspended' => ['suspended'],
        ];
    }

    public function test_admin_cannot_set_an_invalid_account_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen', 'status' => 'active']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->patch(route('admin.users.status', $citizen), [
                'status_user_id' => $citizen->id,
                'status' => 'deleted',
            ])
            ->assertOk()
            ->assertViewHas('statusReopenId', $citizen->id)
            ->assertSee("openUserStatusModal({$citizen->id}, true);");

        $this->assertDatabaseHas('users', ['id' => $citizen->id, 'status' => 'active']);
    }

    public function test_editing_account_details_does_not_change_account_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen', 'status' => 'active']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $citizen), [
                'name' => $citizen->name,
                'email' => $citizen->email,
                'role' => 'citizen',
                'status' => 'suspended',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $citizen->id, 'status' => 'active']);
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
