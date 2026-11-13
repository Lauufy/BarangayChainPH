<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_can_authenticate_through_google(): void
    {
        $user = User::factory()->create([
            'email' => 'citizen@example.com',
            'email_verified_at' => now(),
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-user-id', $user->fresh()->google_id);
    }

    public function test_existing_google_account_with_pending_password_setup_continues_to_setup(): void
    {
        // Signed up with Google earlier and never finished the password step.
        // google_id is already set, so it must NOT be treated as a normal account.
        $user = User::factory()->create([
            'email' => 'legacy-google@example.com',
            'google_id' => 'legacy-google-user-id',
            'email_verified_at' => now(),
            'google_password_setup_required' => true,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => $user->google_id,
            'email' => $user->email,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.password.setup'));

        $this->assertGuest();
        $this->assertSame($user->id, session('google_password_setup.user_id'));
        $this->assertTrue($user->fresh()->google_password_setup_required);
    }

    public function test_existing_account_with_verified_email_skips_password_setup(): void
    {
        $user = User::factory()->create([
            'email' => 'registered@example.com',
            'email_verified_at' => now()->subDay(),
            'phone_verified_at' => null,
            'google_id' => null,
            'google_password_setup_required' => true,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('google_password_setup'));
        $this->assertFalse($user->fresh()->google_password_setup_required);
        $this->assertSame('google-user-id', $user->fresh()->google_id);
    }

    public function test_existing_account_with_verified_phone_skips_password_setup(): void
    {
        $user = User::factory()->create([
            'email' => 'phone-verified@example.com',
            'email_verified_at' => null,
            'phone_verified_at' => now()->subDay(),
            'google_id' => null,
            'google_password_setup_required' => true,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('google_password_setup'));
        $this->assertFalse($user->fresh()->google_password_setup_required);
    }

    public function test_existing_account_with_nothing_verified_keeps_pending_password_setup(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'google_id' => null,
            'google_password_setup_required' => true,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.password.setup'));

        $this->assertGuest();
        $this->assertSame($user->id, session('google_password_setup.user_id'));
        $this->assertTrue($user->fresh()->google_password_setup_required);
    }

    public function test_new_google_account_must_set_a_local_password_before_login(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'new-google-user-id',
            'name' => 'New Citizen',
            'email' => 'new-citizen@example.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('google.password.setup'));
        $this->assertGuest();

        $user = User::where('email', 'new-citizen@example.com')->firstOrFail();
        $this->assertTrue($user->google_password_setup_required);
        $this->assertNotNull($user->email_verified_at);

        $this->get(route('google.password.setup'))
            ->assertOk()
            ->assertSee('Create a password for your account');

        Http::fake([
            'https://api.pwnedpasswords.com/range/*' => Http::response('', 200),
        ]);

        $this->put(route('google.password.setup.store'), [
            'password' => 'LocalPass123!',
            'password_confirmation' => 'LocalPass123!',
        ])->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->fresh()->google_password_setup_required);
        $this->assertTrue(Hash::check('LocalPass123!', $user->fresh()->password));
    }

    public function test_google_password_setup_requires_confirmation_and_keeps_user_logged_out_on_failure(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'new-google-user-id',
            'name' => 'New Citizen',
            'email' => 'new-citizen@example.com',
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.password.setup'));

        $this->put(route('google.password.setup.store'), [
            'password' => 'LocalPass123!',
            'password_confirmation' => 'DifferentPass123!',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertTrue(User::where('email', 'new-citizen@example.com')->firstOrFail()->google_password_setup_required);
    }
}