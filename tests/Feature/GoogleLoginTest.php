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
            'email_verified' => true,
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('google.link.confirm'));
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertSame($user->id, session('google_link_pending.user_id'));
        $this->assertSame('google-user-id', session('google_link_pending.google_id'));
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
            'email_verified' => true,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.password.setup'));

        $this->assertGuest();
        $this->assertSame($user->id, session('google_password_setup.user_id'));
        $this->assertTrue($user->fresh()->google_password_setup_required);
    }

    public function test_existing_account_with_verified_email_requires_link_confirmation(): void
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
            'email_verified' => true,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.link.confirm'));

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertSame($user->id, session('google_link_pending.user_id'));
    }

    public function test_existing_account_with_verified_phone_requires_link_confirmation(): void
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
            'email_verified' => true,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.link.confirm'));

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertSame($user->id, session('google_link_pending.user_id'));
    }

    public function test_existing_account_with_nothing_verified_requires_link_confirmation(): void
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
            'email_verified' => true,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.link.confirm'));

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertSame($user->id, session('google_link_pending.user_id'));
    }

    public function test_new_google_account_must_set_a_local_password_before_login(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'new-google-user-id',
            'name' => 'New Citizen',
            'email' => 'new-citizen@example.com',
            'email_verified' => true,
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
            'email_verified' => true,
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

    public function test_unverified_google_email_is_refused(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => 'unverified-google@example.com',
            'email_verified' => false,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('identifier');

        $this->assertGuest();
        $this->assertNull(session('google_link_pending'));
    }

    public function test_unauthenticated_link_attempt_is_blocked(): void
    {
        $this->get(route('google.link.confirm'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('identifier');

        $this->post(route('google.link.confirm.post'), ['password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('identifier');
    }

    public function test_wrong_password_fails_google_link(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'wrong-password@example.com',
            'password' => Hash::make('correct-password'),
            'google_id' => null,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
            'email_verified' => true,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.link.confirm'));

        $this->post(route('google.link.confirm.post'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'oauth.link_failed',
            'subject_id' => (string) $user->id,
        ]);
    }

    public function test_expired_pending_google_link_fails(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'expired-link@example.com',
            'google_id' => null,
        ]);

        $this->withSession([
            'google_link_pending' => [
                'user_id' => $user->id,
                'google_id' => 'google-user-id',
                'expires_at' => now()->subMinute()->timestamp,
            ],
        ]);

        $this->get(route('google.link.confirm'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('identifier');

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_correct_password_links_google_account(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'correct-password@example.com',
            'password' => Hash::make('correct-password'),
            'google_id' => null,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
            'email_verified' => true,
        ]));

        $this->get('/auth/google/callback')
            ->assertRedirect(route('google.link.confirm'));

        $this->post(route('google.link.confirm.post'), ['password' => 'correct-password'])
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-user-id', $user->fresh()->google_id);
        $this->assertFalse($user->fresh()->google_password_setup_required);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'oauth.link_confirmed',
            'subject_id' => (string) $user->id,
        ]);
    }

    public function test_passwordless_account_can_confirm_google_link_with_otp(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'passwordless@example.com',
            'google_id' => null,
            'google_password_setup_required' => true,
        ]);

        $this->withSession([
            'google_link_pending' => [
                'user_id' => $user->id,
                'google_id' => 'google-user-id',
                'expires_at' => now()->addMinutes(10)->timestamp,
            ],
            'otp' => [
                'purpose' => 'google_link',
                'channel' => 'email',
                'to' => $user->email,
                'name' => $user->name,
                'payload' => [],
                'hash' => Hash::make('123456'),
                'expires' => now()->addMinutes(10)->timestamp,
            ],
        ]);

        $this->get(route('google.link.confirm'))
            ->assertOk()
            ->assertSee('Verification code');

        $this->post(route('google.link.confirm.post'), ['otp' => '123456'])
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-user-id', $user->fresh()->google_id);
    }
}
