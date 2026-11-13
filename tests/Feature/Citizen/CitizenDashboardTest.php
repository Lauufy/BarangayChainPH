<?php

namespace Tests\Feature\Citizen;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_only_the_citizens_own_request_statuses(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);
        $otherCitizen = User::factory()->create(['role' => 'citizen']);

        ServiceRequest::factory()->create([
            'user_id' => $citizen->id,
            'reference_number' => 'OWN-REQUEST-1001',
            'status' => ServiceRequest::PROCESSING,
        ]);
        ServiceRequest::factory()->create([
            'user_id' => $otherCitizen->id,
            'reference_number' => 'OTHER-REQUEST-2002',
            'status' => ServiceRequest::APPROVED,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee('OWN-REQUEST-1001')
            ->assertSee('Processing')
            ->assertDontSee('OTHER-REQUEST-2002');
    }

    public function test_dashboard_shows_an_empty_state_when_the_citizen_has_no_requests(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)
            ->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee('You have not submitted any applications yet.');
    }
}
