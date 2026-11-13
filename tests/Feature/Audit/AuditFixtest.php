<?php

namespace Tests\Feature;

use App\Http\Controllers\Citizen\IdentityVerificationController;
use App\Http\Controllers\PermitVerificationController;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression tests for the first round of audit fixes.
 * Put this file in tests/Feature/AuditFixesTest.php.
 *
 * Assumptions to check against your code:
 *  - The login form posts to /login (change LOGIN_URL if not).
 *  - ServiceRequest::BUSINESS_CLEARANCE exists (it is the constant name I guessed).
 */
class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/login';

    // ---------- Seeder ----------

    public function test_seeder_does_not_create_the_test_user_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_seeder_still_creates_the_test_user_locally(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    // ---------- Mandatory 2FA for privileged roles ----------

    public static function privilegedRoles(): array
    {
        return [['admin'], ['staff']];
    }

    #[DataProvider('privilegedRoles')]
    public function test_privileged_users_cannot_log_in_with_password_only(string $role): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->withoutTwoFactor()->create([
            'role' => $role,
        ]);

        $response = $this->post(self::LOGIN_URL, [
            'identifier' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('verification.otp.show'));
    }

    public function test_citizens_without_2fa_can_still_log_in_directly(): void
    {
        $user = User::factory()->withoutTwoFactor()->create([
            'role' => 'citizen',
        ]);

        $this->post(self::LOGIN_URL, [
            'identifier' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('citizen.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_requires_two_factor_is_true_for_admin_and_staff_even_when_the_flag_is_off(): void
    {
        $admin = User::factory()->withoutTwoFactor()->make(['role' => 'admin']);
        $staff = User::factory()->withoutTwoFactor()->make(['role' => 'staff']);
        $citizen = User::factory()->withoutTwoFactor()->make(['role' => 'citizen']);

        $this->assertTrue($admin->requiresTwoFactor());
        $this->assertTrue($staff->requiresTwoFactor());
        $this->assertFalse($citizen->requiresTwoFactor());
    }

    // ---------- Public verification titles ----------

    public function test_indigency_certificates_use_a_generic_public_title(): void
    {
        $this->assertSame(
            'Barangay Certificate',
            $this->publicTitleFor(ServiceRequest::CERTIFICATE_OF_INDIGENCY)
        );
    }

    public function test_business_clearance_has_its_own_public_title(): void
    {
        $this->assertSame(
            'Barangay Business Clearance',
            $this->publicTitleFor(ServiceRequest::BUSINESS_CLEARANCE)
        );
    }

    public function test_other_service_titles_are_unchanged(): void
    {
        $this->assertSame('Barangay Clearance', $this->publicTitleFor(ServiceRequest::BARANGAY_CLEARANCE));
        $this->assertSame('Certificate of Residency', $this->publicTitleFor(ServiceRequest::CERTIFICATE_OF_RESIDENCY));
    }

    private function publicTitleFor(string $serviceType): string
    {
        $application = new ServiceRequest;
        $application->service_type = $serviceType;

        $controller = new PermitVerificationController;
        $method = new ReflectionMethod($controller, 'documentTitle');

        return $method->invoke($controller, $application);
    }

    // ---------- ID number validation ----------

    public static function acceptedIdNumbers(): array
    {
        return [
            'plain' => ['P1234567A'],
            'dashes' => ['N01-12-123456'],
            'slash (local barangay ID)' => ['2024/00123'],
            'spaces' => ['1234 5678 9012'],
        ];
    }

    #[DataProvider('acceptedIdNumbers')]
    public function test_id_number_formats_the_form_allows_are_accepted_by_the_server(string $number): void
    {
        $this->assertSame([], $this->idNumberErrors($number));
    }

    public static function rejectedIdNumbers(): array
    {
        return [
            'too short' => ['AB12'],
            'leading dash' => ['-12345678'],
            'symbols' => ['AB@12345'],
            'too long' => [str_repeat('1', 51)],
        ];
    }

    #[DataProvider('rejectedIdNumbers')]
    public function test_invalid_id_numbers_are_rejected(string $number): void
    {
        $this->assertContains('id_number', $this->idNumberErrors($number));
    }

    private function idNumberErrors(string $number): array
    {
        $controller = app(IdentityVerificationController::class);
        $rules = (new ReflectionMethod($controller, 'idRules'))->invoke($controller);

        return Validator::make(
            ['document_type' => 'Passport', 'id_number' => $number],
            $rules
        )->errors()->keys();
    }

    public function test_the_unused_id_lookup_endpoint_is_gone(): void
    {
        // It let any logged-in citizen test whether someone else's ID was already registered.
        $this->assertFalse(method_exists(IdentityVerificationController::class, 'checkIdNumber'));
    }
}