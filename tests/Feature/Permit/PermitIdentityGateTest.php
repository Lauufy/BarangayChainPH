<?php

namespace Tests\Feature\Permit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** An unverified citizen must not be able to open or submit any service application. */
class PermitIdentityGateTest extends TestCase
{
    use RefreshDatabase;

    private function unverifiedCitizen(): User
    {
        return User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
    }

    /** Every service slug in config/barangay_services.php. */
    private function slugs(): array
    {
        return array_keys(config('barangay_services.services'));
    }

    public function test_unverified_citizen_is_redirected_from_every_application_form(): void
    {
        $user = $this->unverifiedCitizen();

        foreach ($this->slugs() as $slug) {
            $this->actingAs($user)
                ->get("/citizen/{$slug}")
                ->assertRedirect(route('citizen.identity-verification'));
        }
    }

    public function test_unverified_citizen_cannot_submit_an_application(): void
    {
        $user = $this->unverifiedCitizen();

        foreach ($this->slugs() as $slug) {
            $this->actingAs($user)
                ->post("/citizen/{$slug}", ['agree_truth' => '1'])
                ->assertRedirect(route('citizen.identity-verification'));
        }

        $this->assertDatabaseCount('service_requests', 0);
        $this->assertDatabaseCount('service_request_documents', 0);
    }
}