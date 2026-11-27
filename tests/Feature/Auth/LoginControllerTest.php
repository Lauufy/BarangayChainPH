<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_login_displays_success_toast_on_citizen_dashboard(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'citizen@example.com',
            'password' => 'StrongPass123!',
            'role' => 'citizen',
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ]);

        $response->assertRedirect(route('verification.otp.show'));
        $this->assertGuest();

        $code = null;
        Notification::assertSentOnDemand(EmailVerificationOtp::class, function (
            EmailVerificationOtp $notification,
            array $channels,
            object $notifiable
        ) use (&$code): bool {
            foreach ($notification->toMail($notifiable)->introLines as $line) {
                if (preg_match('/^\d{6}$/', $line)) {
                    $code = $line;
                }
            }

            return $code !== null && in_array('mail', $channels, true);
        });

        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertRedirect(route('citizen.dashboard'))
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
            'two_factor_enabled' => false,
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

    public function test_phone_login_requires_otp_for_unverified_phone_even_when_two_factor_is_off(): void
    {
        config(['services.textbee.key' => 'test-key', 'services.textbee.device' => 'test-device']);
        Http::fake([
            'https://api.textbee.dev/api/v1/gateway/devices/*/send-sms' => Http::response(['ok' => true]),
        ]);
        $user = User::factory()->create([
            'phone' => '+639171234567',
            'password' => 'StrongPass123!',
            'role' => 'citizen',
            'two_factor_enabled' => false,
            'phone_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'mode' => 'phone',
            'phone' => '+639171234567',
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertGuest();
        $this->assertSame('login', session('otp.purpose'));
        $this->assertSame('sms', session('otp.channel'));
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_verified_phone_login_skips_otp_when_two_factor_is_off(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
            'password' => 'StrongPass123!',
            'role' => 'citizen',
            'two_factor_enabled' => false,
        ]);

        $this->post(route('login.store'), [
            'mode' => 'phone',
            'phone' => '+639171234567',
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_account_login_uses_generic_error_and_logs_failed_event(): void
    {
        $this->post(route('login.store'), [
            'identifier' => 'missing@example.com',
            'password' => 'WrongPass123!',
        ])
            ->assertSessionHasErrors([
                'identifier' => 'Invalid credentials.',
            ]);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login.failed',
            'subject_id' => 'unknown',
        ]);
    }

    public function test_wrong_password_uses_generic_error_and_logs_failed_event(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'known@example.com',
        ]);

        $this->post(route('login.store'), [
            'identifier' => $user->email,
            'password' => 'WrongPass123!',
        ])
            ->assertSessionHasErrors([
                'password' => 'Invalid credentials.',
            ]);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login.failed',
            'subject_id' => (string) $user->id,
        ]);
    }

    public function test_account_lockout_logs_lockout_event(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'email' => 'locked@example.com',
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('login.store'), [
                'identifier' => $user->email,
                'password' => 'WrongPass123!',
            ]);
        }

        $this->post(route('login.store'), [
            'identifier' => $user->email,
            'password' => 'WrongPass123!',
        ])
            ->assertSessionHasErrors('login');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login.lockout',
            'subject_id' => (string) $user->id,
        ]);
    }
}
