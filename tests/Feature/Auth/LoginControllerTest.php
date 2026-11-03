<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'is_active' => true,
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
