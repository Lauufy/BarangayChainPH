<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_login_displays_success_toast_on_citizen_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'citizen@example.com',
            'password' => 'StrongPass123!',
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ]);

        $response->assertRedirect(route('citizen.dashboard'))
            ->assertSessionHas('success', 'You are now logged in successfully.');

        $this->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee('toast-container')
            ->assertSee('You are now logged in successfully.');
    }

    public function test_admin_login_displays_success_toast_on_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'StrongPass123!',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'You are now logged in successfully.');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('toast-container')
            ->assertSee('You are now logged in successfully.');
    }
}
