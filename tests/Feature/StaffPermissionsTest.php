<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $permissions = []): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
            'permissions' => $permissions,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_dashboard_is_open_to_every_staff_member(): void
    {
        $this->actingAs($this->staff())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_staff_pages_need_their_permission(): void
    {
        $pages = [
            'admin.reports.index' => 'reports.view',
            'admin.audit-logs.index' => 'audit.view',
            'admin.blockchain-ledger.index' => 'blockchain.view',
            'admin.document-authentication.index' => 'documents.authenticate',
            'admin.permits.index' => 'permits.review',
            'admin.tickets.index' => 'tickets.reply',
        ];

        foreach ($pages as $route => $permission) {
            $this->actingAs($this->staff())->get(route($route))->assertForbidden();
            $this->actingAs($this->staff([$permission]))->get(route($route))->assertOk();
        }
    }

    public function test_users_and_roles_is_admin_only(): void
    {
        $staffWithEverything = $this->staff(array_keys(config('permissions.list')));

        $this->actingAs($staffWithEverything)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_staff_permissions(): void
    {
        $staff = $this->staff();

        $this->actingAs($this->admin())
            ->put(route('admin.users.permissions.update', $staff), ['permissions' => ['reports.view']])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(['reports.view'], $staff->fresh()->permissions);
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $staff = $this->staff(['reports.view']);

        $this->actingAs($this->admin())
            ->put(route('admin.users.permissions.update', $staff), ['permissions' => ['everything']])
            ->assertSessionHasErrors('permissions.0');

        $this->assertSame(['reports.view'], $staff->fresh()->permissions);
    }

    public function test_staff_cannot_change_their_own_permissions(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)
            ->put(route('admin.users.permissions.update', $staff), [
                'permissions' => array_keys(config('permissions.list')),
            ])
            ->assertForbidden();

        $this->assertSame([], $staff->fresh()->permissions);
    }

    public function test_permissions_page_is_only_for_staff_accounts(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.users.permissions', $admin))
            ->assertNotFound();
    }
}