<?php

namespace Tests\Feature\Audit;

use App\Http\Controllers\Citizen\IdentityVerificationController;
use App\Http\Controllers\Shared\PermitVerificationController;
use App\Models\CitizenProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/login';

    // ---------- Seeder ----------

    public function test_seeder_does_not_create_the_test_user_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        (new DatabaseSeeder)->run();

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_seeder_still_creates_the_test_user_locally(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        (new DatabaseSeeder)->run();

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    // ---------- Mandatory 2FA for privileged roles ----------

    public static function privilegedRoles(): array
    {
        return [['admin'], ['staff']];
    }

    #[DataProvider('privilegedRoles')]
    public function test_privileged_users_with_2fa_cannot_log_in_with_password_only(string $role): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->create([
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

    // ---------- Two-sided ID upload without number validation ----------

    public function test_id_number_is_not_saved_when_a_citizen_stages_both_id_photos(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen']);
        CitizenProfile::create([
            'user_id' => $citizen->id,
            'date_of_birth' => '1990-01-01',
            'address' => '1 Main Street, San Luis',
            'barangay' => 'San Luis',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'place_of_birth' => 'San Luis',
            'city_municipality' => 'San Luis',
            'province' => 'Province',
            'zip_code' => '1234',
        ]);
        Storage::fake('supabase');

        $response = $this->actingAs($citizen)
            ->withHeader('Accept', 'application/json')
            ->post(route('citizen.identity-verification.id'), [
                'document_type' => 'Passport',
                'id_number' => 'INVALID @ VALUE',
                'document' => UploadedFile::fake()->image('passport-front.jpg'),
                'document_back' => UploadedFile::fake()->image('passport-back.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $response->assertSessionHas('identity_verification.draft.document_type', 'Passport');
        $this->assertDatabaseCount('identity_verifications', 0);
        $this->assertArrayNotHasKey('id_number', session('identity_verification.draft'));
    }

    public function test_the_unused_id_lookup_endpoint_is_gone(): void
    {
        // It let any logged-in citizen test whether someone else's ID was already registered.
        $this->assertFalse(method_exists(IdentityVerificationController::class, 'checkIdNumber'));
    }
}
