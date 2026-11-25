<?php

namespace Tests\Feature\Admin;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private const BLOCKED_DELETE_MESSAGE = 'This user has issued permits and cannot be deleted. Issued permits must be preserved for QR verification and blockchain records.';

    public function test_user_with_issued_permits_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen']);
        $serviceRequest = ServiceRequest::factory()
            ->for($citizen)
            ->create(['status' => ServiceRequest::APPROVED]);
        $issuedDocument = IssuedDocument::factory()->for($serviceRequest)->create();

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('admin.users.destroy', $citizen))
            ->assertOk()
            ->assertSee("showToast('error',", false)
            ->assertSee(self::BLOCKED_DELETE_MESSAGE, false)
            ->assertDontSee('User deleted successfully.', false);

        $this->assertDatabaseHas('users', ['id' => $citizen->id]);
        $this->assertDatabaseHas('service_requests', ['id' => $serviceRequest->id]);
        $this->assertDatabaseHas('issued_documents', ['id' => $issuedDocument->id]);
    }

    public function test_user_with_requests_without_issued_permits_can_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen']);
        $serviceRequest = ServiceRequest::factory()->for($citizen)->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $citizen))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $citizen->id]);
        $this->assertDatabaseMissing('service_requests', ['id' => $serviceRequest->id]);
    }

    public function test_successful_delete_shows_the_success_toast(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('admin.users.destroy', $citizen))
            ->assertOk()
            ->assertSee("showToast('success',", false)
            ->assertSee('User deleted successfully.', false);

        $this->assertDatabaseMissing('users', ['id' => $citizen->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('admin.users.destroy', $admin))
            ->assertOk()
            ->assertSee("showToast('error',", false)
            ->assertSee('You cannot delete your own account.', false);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_staff_cannot_delete_users(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($staff)
            ->delete(route('admin.users.destroy', $citizen));

        $this->assertDatabaseHas('users', ['id' => $citizen->id]);
    }

    public function test_user_actions_offer_status_management_without_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $citizen = User::factory()->create(['role' => 'citizen', 'name' => 'Alex Rivera']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('onclick="openUserStatusModal('.$citizen->id.')"', false)
            ->assertSee('id="userStatusModal"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('value="active"', false)
            ->assertSee('value="inactive"', false)
            ->assertSee('value="suspended"', false)
            ->assertDontSee('Delete user?', false)
            ->assertDontSee('id="editUser_status"', false);
    }

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
                && $records->has($citizen->id))
            ->assertSee('id="viewUserModal"', false)
            ->assertSee('id="editUserModal"', false)
            ->assertSee('onclick="openViewUserModal('.$staff->id.')"', false)
            ->assertSee('onclick="openEditUserModal('.$staff->id.')"', false);

        $this->get(route('admin.users.index', ['users_tab' => 'employees']))
            ->assertOk()
            ->assertViewHas('view', 'employees')
            ->assertViewHas('paginator', fn ($paginator): bool => $paginator->getPageName() === 'staff_page');

        $this->get(route('admin.users.index', ['users_tab' => 'citizens']))
            ->assertOk()
            ->assertViewHas('view', 'citizens')
            ->assertViewHas('paginator', fn ($paginator): bool => $paginator->getPageName() === 'citizen_page');
    }

    public function test_administrator_tab_and_status_filter_select_only_matching_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $inactive = User::factory()->create(['role' => 'admin', 'status' => 'inactive']);
        User::factory()->create(['role' => 'staff', 'status' => 'inactive']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['users_tab' => 'administrators', 'users_status' => 'inactive']))
            ->assertOk()
            ->assertViewHas('userRecords', fn (Collection $records): bool => $records->keys()->all() === [$inactive->id])
            ->assertViewHas('accountCounts', fn (array $counts): bool => $counts['admin'] === 2 && $counts['staff'] === 1);
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
            ->assertSee('placeholder="Search by name, email, or phone"', false)
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
                'email' => 'not-an-email',
                'role' => 'staff',
                'employment_type' => 'full_time',
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
