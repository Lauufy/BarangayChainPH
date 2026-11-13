<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ensures the dashboard names the identity-verification count accurately. */
class DashboardLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_labels_verified_identity_records(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Verified identities')
            ->assertSee('Verified identity records');
    }
}
