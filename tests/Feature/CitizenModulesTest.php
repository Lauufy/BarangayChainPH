<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_render_each_portal_module(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]));

        foreach ($this->citizenModulePages() as $routeName => $viewName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertViewIs($viewName);
        }
    }

    public function test_guest_is_redirected_to_login_from_each_portal_module(): void
    {
        foreach (array_keys($this->citizenModulePages()) as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }
    }

    public function test_non_citizen_is_forbidden_from_each_portal_module(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]));

        foreach (array_keys($this->citizenModulePages()) as $routeName) {
            $this->get(route($routeName))
                ->assertForbidden();
        }
    }

    /**
     * @return array<string, string>
     */
    private function citizenModulePages(): array
    {
        return [
            'citizen.dashboard' => 'citizen.dashboard',
            'citizen.services' => 'citizen.services',
            'citizen.documents' => 'citizen.documents',
            'citizen.applications' => 'citizen.applications',
            'citizen.profile' => 'citizen.profile',
            'citizen.identity-verification' => 'citizen.identity-verification',
        ];
    }
}
