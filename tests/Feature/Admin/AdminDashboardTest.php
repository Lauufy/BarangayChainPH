<?php

namespace Tests\Feature\Admin;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_permit_requests_are_listed(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
            'is_active' => true,
        ]);
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);

        ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-DASH-0001',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Test Applicant',
            'address' => 'Barangay San Luis',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('BRGY-DASH-0001');
    }

    public function test_user_management_shortcut_is_only_shown_to_admins(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertDontSee('User & Role Management');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee('User & Role Management');
    }
}