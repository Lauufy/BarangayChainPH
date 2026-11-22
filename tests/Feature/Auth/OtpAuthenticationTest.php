<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\IdentityVerification;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use App\Notifications\IdentityVerificationReviewed;
use App\Notifications\ServiceRequestApproved;
use App\Notifications\ServiceRequestRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OtpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.textbee.key' => 'test-textbee-key',
            'services.textbee.device' => 'test-device',
            'services.recaptcha.secret_key' => 'test-recaptcha-secret',
        ]);
    }

    public function test_user_table_does_not_store_email_verification_codes(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'email_verification_otp'));
        $this->assertFalse(Schema::hasColumn('users', 'email_verification_otp_expires_at'));
    }

    public function test_email_signup_creates_and_logs_in_the_user_only_after_the_code_is_verified(): void
    {
        $this->fakeHttp();
        Notification::fake();
        config(['privacy.policy_version' => '2026-11-v1']);
        $oldSessionId = $this->app['session']->getId();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.44'])
            ->post(route('register.store'), $this->registrationData([
                'mode' => 'email',
                'email' => 'new@example.com',
            ]));

        $response->assertRedirect(route('verification.otp.show'));
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
        $code = $this->emailCode('new@example.com');
        $this->assertNotSame($code, session('otp.hash'));
        $this->assertTrue(Hash::check($code, session('otp.hash')));

        $this->post(route('verification.otp.verify'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);

        $response = $this->post(route('verification.otp.verify'), ['otp' => $code]);
        $response->assertRedirect(route('citizen.dashboard'));

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->consent_given_at);
        $this->assertSame('2026-11-v1', $user->privacy_policy_version);
        $this->assertSame('203.0.113.44', $user->consent_ip);
        $this->assertNull($user->phone);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSessionId, $this->app['session']->getId());
        $registrationAudit = AuditLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', (string) $user->id)
            ->where('action', 'users.created')
            ->firstOrFail();
        $this->assertSame('[redacted]', $registrationAudit->new_values['consent_ip']);
        Notification::assertSentOnDemandOnce(EmailVerificationOtp::class);
    }

    public function test_email_signup_rejects_a_duplicate_email(): void
    {
        $this->fakeHttp();
        Notification::fake();
        User::factory()->create(['email' => 'existing@example.com']);

        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'email',
            'email' => 'existing@example.com',
        ]))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        Notification::assertNothingSent();
    }

    public function test_registration_is_throttled_after_ten_attempts(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.45'])
                ->post(route('register.store'), [])
                ->assertInvalid(['name']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.45'])
            ->post(route('register.store'), [])
            ->assertTooManyRequests();
    }

    public function test_unverified_email_must_be_verified_before_channel_choice_and_login(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = $this->citizen([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertSame('email', session('otp.channel'));
        $this->assertGuest();
        $code = $this->emailCode($user->email);

        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertSentOnDemandOnce(EmailVerificationOtp::class);
        Http::assertNothingSent();
    }

    public function test_email_signup_rejects_an_expired_code(): void
    {
        $this->fakeHttp();
        Notification::fake();

        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'email',
            'email' => 'expired@example.com',
        ]));
        $code = $this->emailCode('expired@example.com');
        $this->travel(11)->minutes();

        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseMissing('users', ['email' => 'expired@example.com']);
    }

    public function test_phone_signup_saves_a_verified_normalized_number_without_an_email(): void
    {
        $this->fakeHttp();

        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '0917-123-4567',
        ]))->assertRedirect(route('verification.otp.show'));

        $this->assertDatabaseCount('users', 0);
        $code = $this->smsCode('+639171234567');
        $this->assertNotSame($code, session('otp.hash'));
        $this->assertTrue(Hash::check($code, session('otp.hash')));

        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertRedirect(route('citizen.dashboard'));

        $user = User::where('phone', '+639171234567')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_phone_signup_rejects_a_duplicate_number_and_an_invalid_number(): void
    {
        $this->fakeHttp();
        User::factory()->create(['phone' => '+639171234567']);

        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '9171234567',
        ]))->assertSessionHasErrors('phone');

        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '12345',
        ]))->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_login_sends_a_code_to_an_email_only_citizen(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = $this->citizen(['email' => 'email-only@example.com']);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertGuest();
        $this->emailCode($user->email);
        Notification::assertSentOnDemandOnce(EmailVerificationOtp::class);
    }

    public function test_login_is_limited_to_ten_attempts_per_minute(): void
    {
        $login = [
            'mode' => 'email',
            'identifier' => 'unknown@example.com',
            'password' => 'incorrect',
        ];

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('login.store'), $login);
        }

        $this->post(route('login.store'), $login)->assertStatus(429);
    }

    public function test_phone_login_sends_a_code_to_a_verified_phone_only_citizen(): void
    {
        $this->fakeHttp();
        $user = $this->citizen([
            'email' => null,
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'mode' => 'phone',
            'phone' => '9171234567',
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertSame('+639171234567', session('otp.to'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->smsCode('+639171234567'));
        $this->assertGuest();
    }

    public function test_phone_login_sends_sms_when_the_account_also_has_an_email(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = $this->citizen([
            'email' => 'phone-login@example.com',
            'phone' => '+639171234567',
            'phone_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'mode' => 'phone',
            'phone' => $user->phone,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertSame('sms', session('otp.channel'));
        $this->assertSame($user->phone, session('otp.to'));
        $this->assertNull($user->phone_verified_at);
        $code = $this->smsCode($user->phone);
        $this->assertGuest();
        Http::assertSentCount(1);
        Notification::assertNothingSent();

        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertRedirect(route('citizen.dashboard'));

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_login_accepts_an_employee_id(): void
    {
        $this->fakeHttp();
        $user = User::factory()->create([
            'email' => 'staff@example.com',
            'role' => 'staff',
            'password' => 'StrongPass123!',
            'two_factor_enabled' => false,
        ]);
        $user->employeeProfile()->create([
            'employee_id' => 'BSLCNPH-000001',
            'department' => 'Office',
            'position' => 'Staff',
        ]);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => 'bslcnph-000001',
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('passwordOnlyRoles')]
    public function test_staff_and_admin_login_without_an_otp(string $role): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = User::factory()->create([
            'email' => "{$role}@example.com",
            'role' => $role,
            'password' => 'StrongPass123!',
            'two_factor_enabled' => false,
        ]);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
        Http::assertNothingSent();
    }

    public static function passwordOnlyRoles(): array
    {
        return ['staff' => ['staff'], 'admin' => ['admin']];
    }

    public function test_login_rejects_wrong_password_and_unknown_user(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = $this->citizen(['email' => 'active@example.com']);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $user->email,
            'password' => 'WrongPass123!',
        ])->assertSessionHasErrors('identifier');

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => 'missing@example.com',
            'password' => 'StrongPass123!',
        ])->assertSessionHasErrors('identifier');

        Notification::assertNothingSent();
    }

    public function test_citizen_with_email_and_verified_phone_must_choose_a_channel(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = $this->citizen([
            'email' => 'both@example.com',
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('two-factor.choose'));

        $this->get(route('two-factor.choose'))
            ->assertOk()
            ->assertViewHas('channels', fn (array $channels): bool => array_keys($channels) === ['email', 'sms'])
            ->assertSee('Choose where we should send your login code.')
            ->assertSee('b***@example.com')
            ->assertSee('+63917****567')
            ->assertSee('name="channel" value="email"', false)
            ->assertSee('name="channel" value="sms"', false);
        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_unverified_phone_is_not_offered_for_login(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = $this->citizen([
            'email' => 'unverified-phone@example.com',
            'phone' => '+639171234567',
            'phone_verified_at' => null,
        ]);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertSame('email', session('otp.channel'));
        Notification::assertSentOnDemandOnce(EmailVerificationOtp::class);
        Http::assertNothingSent();
    }

    public function test_two_factor_rejects_a_channel_not_available_to_the_user(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $user = $this->citizen([
            'email' => 'invalid-channel@example.com',
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);
        $this->startTwoFactorLogin($user);

        $this->post(route('two-factor.send'), ['channel' => 'whatsapp'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('otp');

        Http::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_remember_me_survives_channel_selection_and_otp_login(): void
    {
        $this->fakeHttp();
        $user = $this->citizen([
            'email' => 'remember@example.com',
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);
        $this->startTwoFactorLogin($user, true);

        $this->assertTrue(session('login_pending.remember'));
        $this->post(route('two-factor.send'), ['channel' => 'sms'])
            ->assertRedirect(route('verification.otp.show'));
        $this->assertTrue(session('otp.payload.remember'));
        $code = $this->smsCode('+639171234567');

        $response = $this->post(route('verification.otp.verify'), ['otp' => $code]);

        $response->assertRedirect(route('citizen.dashboard'));
        $response->assertCookie($this->app['auth']->guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_login_expires_after_ten_minutes(): void
    {
        $this->fakeHttp();
        $user = $this->citizen([
            'email' => 'pending@example.com',
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);
        $this->startTwoFactorLogin($user);
        $this->travel(11)->minutes();

        $this->get(route('two-factor.choose'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('otp');
    }

    public function test_five_wrong_codes_lock_verification_until_resend(): void
    {
        $this->fakeHttp();
        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '9171234567',
        ]));

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('verification.otp.verify'), ['otp' => '000000'])
                ->assertSessionHasErrors('otp');
        }

        $this->post(route('verification.otp.verify'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_resend_is_limited_to_once_per_120_seconds_and_resets_wrong_tries(): void
    {
        $this->fakeHttp();
        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '9171234567',
        ]));
        $this->post(route('verification.otp.verify'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->post(route('verification.otp.resend'))
            ->assertSessionHasNoErrors();
        $this->post(route('verification.otp.resend'))
            ->assertSessionHasErrors('otp');

        $this->travel(120)->seconds();
        $this->post(route('verification.otp.resend'))
            ->assertSessionHasNoErrors();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('verification.otp.verify'), ['otp' => '000000'])
                ->assertSessionHasErrors('otp');
        }

        $this->post(route('verification.otp.verify'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_sixth_sms_to_a_number_within_an_hour_is_blocked(): void
    {
        $this->fakeHttp();
        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '9171234567',
        ]));

        for ($sms = 1; $sms < 5; $sms++) {
            $this->travel(120)->seconds();
            $this->post(route('verification.otp.resend'))->assertSessionHasNoErrors();
        }

        $this->travel(120)->seconds();
        $this->post(route('verification.otp.resend'))
            ->assertSessionHasErrors('otp');
        Http::assertSentCount(7);
    }

    public function test_a_verified_code_cannot_be_reused(): void
    {
        $this->fakeHttp();
        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '9171234567',
        ]));
        $code = $this->smsCode('+639171234567');

        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertRedirect(route('citizen.dashboard'));
        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_sms_password_reset_requires_a_verified_phone_and_changes_the_password(): void
    {
        $this->fakeHttp();
        $user = $this->citizen([
            'email' => null,
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);

        $this->post(route('password.email'), [
            'mode' => 'phone',
            'phone' => '9171234567',
        ])->assertRedirect(route('verification.otp.show'));
        $code = $this->smsCode('+639171234567');
        $this->post(route('verification.otp.verify'), ['otp' => $code])
            ->assertRedirect(route('password.sms.show'));
        $this->post(route('password.sms.update'), [
            'password' => 'NewStrong123!',
            'password_confirmation' => 'NewStrong123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewStrong123!', $user->fresh()->password));
        $this->assertNull(session('sms_reset'));
    }

    public function test_sms_password_reset_page_is_available_after_code_verification(): void
    {
        $this->fakeHttp();
        $this->citizen([
            'email' => null,
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);
        $this->post(route('password.email'), [
            'mode' => 'phone',
            'phone' => '9171234567',
        ]);
        $this->post(route('verification.otp.verify'), ['otp' => $this->smsCode('+639171234567')])
            ->assertRedirect(route('password.sms.show'));

        $this->get(route('password.sms.show'))
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertViewHas('isSmsReset', true)
            ->assertSee('action="'.route('password.sms.update').'"', false)
            ->assertDontSee('name="token"', false)
            ->assertDontSee('name="email"', false);
    }

    public function test_sms_password_reset_rejects_unknown_and_unverified_phones(): void
    {
        $this->fakeHttp();
        $this->citizen([
            'email' => 'unverified@example.com',
            'phone' => '+639171234567',
            'phone_verified_at' => null,
        ]);

        $this->post(route('password.email'), [
            'mode' => 'phone',
            'phone' => '09171234567',
        ])->assertSessionHasErrors('phone');

        $this->post(route('password.email'), [
            'mode' => 'phone',
            'phone' => '09187654321',
        ])->assertSessionHasErrors('phone');

        Http::assertNothingSent();
    }

    #[DataProvider('invalidPasswords')]
    public function test_sms_password_reset_enforces_password_rules(string $password): void
    {
        $this->fakeHttp();
        $user = $this->citizen(['email' => 'rules@example.com']);
        $this->withSession(['sms_reset' => [
            'user_id' => $user->id,
            'expires' => now()->addMinutes(10)->timestamp,
        ]])->post(route('password.sms.update'), [
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');
    }

    public static function invalidPasswords(): array
    {
        return [
            'too short' => ['Ab1!'],
            'missing uppercase' => ['lowercase123!'],
            'missing lowercase' => ['UPPERCASE123!'],
            'missing number' => ['StrongPass!'],
            'missing symbol' => ['StrongPass123'],
            'over sixteen characters' => ['LongerThanSixteen123!'],
        ];
    }

    public function test_sms_password_reset_session_expires_after_ten_minutes(): void
    {
        $this->fakeHttp();
        $user = $this->citizen(['email' => 'reset-expired@example.com']);
        $this->withSession(['sms_reset' => [
            'user_id' => $user->id,
            'expires' => now()->addMinutes(10)->timestamp,
        ]]);
        $this->travel(11)->minutes();

        $this->post(route('password.sms.update'), [
            'password' => 'NewStrong123!',
            'password_confirmation' => 'NewStrong123!',
        ])->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('phone');
        $this->assertTrue(Hash::check('StrongPass123!', $user->fresh()->password));
    }

    public function test_email_password_reset_link_still_works(): void
    {
        $this->fakeHttp();
        $user = $this->citizen(['email' => 'link-reset@example.com']);
        $token = Password::createToken($user);

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertViewHas('isSmsReset', false)
            ->assertSee('value="'.$token.'"', false)
            ->assertSee($user->email)
            ->assertSee('action="'.route('password.update').'"', false);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewStrong123!',
            'password_confirmation' => 'NewStrong123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewStrong123!', $user->fresh()->password));
    }

    public function test_textbee_server_error_is_reported_without_creating_a_user_or_logging_the_code(): void
    {
        $this->fakeHttp(fn () => Http::response(['error' => 'failed'], 500));
        $exceptions = Exceptions::fake();
        Log::spy();

        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '9171234567',
        ]))->assertSessionHasErrors('otp');

        $code = $this->smsCode('+639171234567');
        $exceptions->assertReported(RequestException::class);
        $this->assertStringNotContainsString($code, $exceptions->reported()[0]->getMessage());
        $this->assertDatabaseCount('users', 0);
        $this->assertNull(session('otp'));
        Log::shouldNotHaveReceived('info', [
            "SMS to +639171234567: Your BarangayChain PH code is {$code}. It expires in 10 minutes. Do not share it.",
        ]);
    }

    public function test_textbee_timeout_shows_a_friendly_error_and_leaves_no_partial_signup(): void
    {
        $this->fakeHttp(function () {
            throw new ConnectionException('Timed out.');
        });
        $exceptions = Exceptions::fake();

        $this->post(route('register.store'), $this->registrationData([
            'mode' => 'phone',
            'phone' => '9171234567',
        ]))->assertSessionHasErrors('otp');

        $exceptions->assertReported(ConnectionException::class);
        $this->assertDatabaseCount('users', 0);
        $this->assertNull(session('otp'));
    }

    public function test_phone_only_account_pages_render_with_a_null_email(): void
    {
        $this->fakeHttp();
        $user = $this->citizen([
            'email' => null,
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user)->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee('+639171234567');
    }

    public function test_identity_details_save_the_contact_phone_on_the_user(): void
    {
        $user = $this->citizen(['email' => 'identity-phone@example.com']);

        $this->actingAs($user)->post(route('citizen.identity-verification.details'), [
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'place_of_birth' => 'Antipolo',
            'barangay' => 'San Luis',
            'city_municipality' => 'Antipolo',
            'province' => 'Rizal',
            'zip_code' => '1870',
        ])->assertRedirect(route('citizen.identity-verification', ['step' => 2]));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+639171234567',
        ]);

        $this->actingAs($user)->get(route('citizen.identity-verification'))
            ->assertOk()
            ->assertSee('+639171234567');
    }

    public function test_identity_details_ignore_the_current_users_phone_but_reject_another_users_phone(): void
    {
        $user = $this->citizen([
            'phone' => '+639171234567',
            'phone_verified_at' => now(),
        ]);
        $this->citizen([
            'email' => 'other-citizen@example.com',
            'phone' => '+639181234567',
        ]);

        $this->actingAs($user)->post(route('citizen.identity-verification.details'), $this->identityDetailsData(
            '+63 917 123 4567'
        ))->assertRedirect(route('citizen.identity-verification', ['step' => 2]));

        $this->assertNotNull($user->fresh()->phone_verified_at);

        $this->post(route('citizen.identity-verification.details'), $this->identityDetailsData(
            '+639181234567'
        ))->assertSessionHasErrors('phone');
    }

    public function test_admin_user_listing_displays_the_users_phone_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->citizen([
            'email' => 'listed-phone@example.com',
            'phone' => '+639171234567',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('+639171234567');
    }

    public function test_identity_review_notification_sends_mail_only_when_the_user_has_an_email(): void
    {
        $this->assertNotificationChannels(new IdentityVerificationReviewed(new IdentityVerification));
    }

    public function test_service_request_approved_notification_sends_mail_only_when_the_user_has_an_email(): void
    {
        $this->assertNotificationChannels(new ServiceRequestApproved(new ServiceRequest));
    }

    public function test_service_request_rejected_notification_sends_mail_only_when_the_user_has_an_email(): void
    {
        $this->assertNotificationChannels(new ServiceRequestRejected(new ServiceRequest));
    }

    private function fakeHttp(?\Closure $smsResponse = null): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
            'https://api.textbee.dev/api/v1/gateway/devices/*/send-sms' => $smsResponse
                ?? Http::response(['ok' => true]),
            'https://api.pwnedpasswords.com/range/*' => Http::response(''),
        ]);
    }

    private function registrationData(array $contact): array
    {
        return array_merge([
            'name' => 'Test Citizen',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'terms' => '1',
            'g-recaptcha-response' => 'valid-captcha',
        ], $contact);
    }

    private function emailCode(?string $expectedRecipient = null): string
    {
        $code = null;
        Notification::assertSentOnDemand(EmailVerificationOtp::class, function (
            EmailVerificationOtp $notification,
            array $channels,
            object $notifiable
        ) use (&$code, $expectedRecipient): bool {
            if ($expectedRecipient !== null) {
                $this->assertSame($expectedRecipient, $notifiable->routeNotificationFor('mail'));
            }

            foreach ($notification->toMail($notifiable)->introLines as $line) {
                if (preg_match('/^\d{6}$/', $line)) {
                    $code = $line;
                }
            }

            return $code !== null && in_array('mail', $channels, true);
        });

        return $code ?? throw new \LogicException('No email OTP code was found.');
    }

    private function smsCode(?string $expectedRecipient = null): string
    {
        $code = null;
        Http::assertSent(function (ClientRequest $request) use (&$code, $expectedRecipient): bool {
            if (! str_contains($request->url(), 'textbee')) {
                return false;
            }

            if ($expectedRecipient !== null) {
                $this->assertSame([$expectedRecipient], $request->data()['recipients']);
            }
            preg_match('/\b(\d{6})\b/', $request->data()['message'], $matches);
            $code = $matches[1] ?? null;

            return $code !== null;
        });

        return $code ?? throw new \LogicException('No SMS OTP code was found.');
    }

    private function citizen(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'citizen@example.com',
            'password' => 'StrongPass123!',
            'role' => 'citizen',
        ], $attributes));
    }

    private function identityDetailsData(string $phone): array
    {
        return [
            'phone' => $phone,
            'date_of_birth' => '1990-01-01',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'place_of_birth' => 'Antipolo',
            'barangay' => 'San Luis',
            'city_municipality' => 'Antipolo',
            'province' => 'Rizal',
            'zip_code' => '1870',
        ];
    }

    private function assertNotificationChannels(BaseNotification $notification): void
    {
        $emailUser = User::factory()->make(['email' => 'citizen@example.com']);
        $phoneOnlyUser = User::factory()->make([
            'email' => null,
            'phone' => '+639171234567',
        ]);

        $this->assertSame(['database', 'mail'], call_user_func([$notification, 'via'], $emailUser));
        $this->assertSame(['database'], call_user_func([$notification, 'via'], $phoneOnlyUser));
    }

    private function startTwoFactorLogin(User $user, bool $remember = false): void
    {
        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $user->email,
            'password' => 'StrongPass123!',
            'remember' => $remember,
        ])->assertRedirect(route('two-factor.choose'));
    }

    public function test_staff_with_two_factor_on_lands_on_the_admin_dashboard_after_the_code(): void
    {
        $this->fakeHttp();
        Notification::fake();
        $staff = User::factory()->create([
            'email' => 'staff-2fa@example.com',
            'role' => 'staff',
            'password' => 'StrongPass123!',
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);

        $this->post(route('login.store'), [
            'mode' => 'email',
            'identifier' => $staff->email,
            'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.otp.show'));

        $this->assertGuest();

        $this->post(route('verification.otp.verify'), ['otp' => $this->emailCode($staff->email)])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($staff);
    }
}
