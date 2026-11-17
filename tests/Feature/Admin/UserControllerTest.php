<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_renders_each_tab_and_supplies_records_for_the_modals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => []]);
        $citizen = User::factory()->create(['role' => 'citizen']);

        $allResponse = $this->actingAs($admin)->get(route('admin.users.index'));

        $allResponse->assertOk()
            ->assertViewHas('view', 'all')
            ->assertViewHas('paginator', fn ($paginator): bool => $paginator->getPageName() === 'all_page')
            ->assertViewHas('userRecords', fn (Collection $records): bool => $records->has($staff->id)
                && $records->has($citizen->id));

        $this->get(route('admin.users.index', ['users_tab' => 'employees']))
            ->assertOk()
            ->assertViewHas('view', 'employees')
            ->assertViewHas('paginator', fn ($paginator): bool => $paginator->getPageName() === 'staff_page');

        $this->get(route('admin.users.index', ['users_tab' => 'citizens']))
            ->assertOk()
            ->assertViewHas('view', 'citizens')
            ->assertViewHas('paginator', fn ($paginator): bool => $paginator->getPageName() === 'citizen_page');
    }

    public function test_invalid_user_tab_falls_back_to_all_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['users_tab' => 'unknown']))
            ->assertOk()
            ->assertViewHas('view', 'all')
            ->assertViewHas('paginator', fn ($paginator): bool => $paginator->getPageName() === 'all_page');
    }

    public function test_user_search_query_is_ignored_and_client_search_toolbar_is_rendered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Visible Staff']);
        $citizen = User::factory()->create(['role' => 'citizen', 'name' => 'Visible Citizen']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'no-match']))
            ->assertOk()
            ->assertViewHas('paginator', fn ($paginator): bool => $paginator->total() === 3)
            ->assertViewHas('userRecords', fn (Collection $records): bool => $records->has($staff->id)
                && $records->has($citizen->id))
            ->assertSee('data-table-key="users"', false)
            ->assertSee('placeholder="Start searching..."', false)
            
            ->assertDontSee('search=')
            ->assertDontSee('search=no-match');
    }

    public function test_create_validation_reopens_the_create_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'role' => 'staff',
                'department' => '',
                'position' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertOk()
            ->assertViewHas('createFailed', true)
            ->assertSee('id="createUserModal"', false)
            ->assertSee('class="flex fixed inset-0', false);
    }

    public function test_update_validation_reopens_the_edit_modal_with_old_input(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => []]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->put(route('admin.users.update', $staff), [
                'edit_user_id' => $staff->id,
                'name' => 'Changed name',
                'email' => $staff->email,
                'role' => 'citizen',
                'department' => 'Administration',
                'position' => 'Clerk',
            ])
            ->assertOk()
            ->assertViewHas('reopenId', $staff->id)
            ->assertViewHas('editOld', fn (array $oldInput): bool => $oldInput['name'] === 'Changed name')
            ->assertSee("openEditUserModal({$staff->id}, true);");
    }

    public function test_permission_validation_reopens_the_permissions_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => []]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->followingRedirects()
            ->put(route('admin.users.permissions.update', $staff), [
                'permissions_user_id' => $staff->id,
                'permissions' => ['unknown.permission'],
            ])
            ->assertOk()
            ->assertViewHas('permissionsReopenId', $staff->id)
            ->assertSee("openPermissionsModal({$staff->id}, true);");
    }
}
