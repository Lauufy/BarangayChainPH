<?php

namespace Tests\Feature\Admin;

use App\Models\IdentityVerification;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_marks_report_charts_for_initialization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-reports-page', false);
    }

    public function test_recent_permit_requests_are_listed(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        $citizen = User::factory()->create(['role' => 'citizen']);

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
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertDontSee('User & Role Management');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee('User & Role Management');
    }

    public function test_staff_dashboard_shows_workload_from_permitted_shared_queues(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review', 'identity.review'],
        ]);
        $citizen = User::factory()->create(['role' => 'citizen']);
        ServiceRequest::factory()->create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-WORKLOAD-0001',
            'status' => ServiceRequest::PENDING,
            'created_at' => now()->subDays(4),
        ]);
        IdentityVerification::factory()->create([
            'user_id' => $citizen->id,
            'status' => IdentityVerification::PENDING,
        ]);

        $response = $this->actingAs($staff)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('My workload')
            ->assertSee('Requests awaiting action')
            ->assertSee('Identity reviews in queue')
            ->assertSee('BRGY-WORKLOAD-0001');

        // Whitespace-tolerant: matches "4 days" even if the markup wraps across lines.
        $this->assertMatchesRegularExpression('/\b4\s+days\s+old\b/', $response->getContent());
    }
}
