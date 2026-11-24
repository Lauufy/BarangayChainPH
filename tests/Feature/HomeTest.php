<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_links_to_the_public_permit_upload_form(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('permits.verify.form').'"', false);

        $this->get(route('permits.verify.form'))
            ->assertOk()
            ->assertViewIs('permits.upload')
            ->assertSee('Permit number or QR reference')
            ->assertDontSee('Verification result');
    }

    public function test_home_displays_the_citizens_dashboard_link_when_signed_in(): void
    {
        $user = User::factory()->create(['role' => 'citizen']);
        $this->actingAs($user)->assertAuthenticatedAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertViewHas('dashboard', route('citizen.dashboard'))
            ->assertSee('to Dashboard')
            ->assertSee('href="'.route('citizen.dashboard').'"', false)
            ->assertDontSee('Sign in');
    }

    public function test_home_displays_the_admin_dashboard_link_when_admin_is_signed_in(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('home'))
            ->assertOk()
            ->assertSee('to Dashboard')
            ->assertSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee('Sign in');
    }

    public function test_home_displays_the_admin_dashboard_link_when_staff_is_signed_in(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('home'))
            ->assertOk()
            ->assertSee('to Dashboard')
            ->assertSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee('Sign in');
    }

    public function test_home_displays_sign_in_after_logout(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'citizen']))
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('dashboard', null)
            ->assertSee('Sign in');
    }
}
