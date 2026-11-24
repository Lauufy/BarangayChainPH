<?php

namespace Tests\Feature\Citizen;

use App\Jobs\ProcessIdentityOcr;
use App\Models\CitizenProfile;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\SupabaseStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_scanner_shows_both_side_uploads_below_one_camera_control(): void
    {
        $citizen = $this->citizenWithCompleteDetails();

        $this->actingAs($citizen)
            ->get(route('citizen.identity-verification', ['step' => 2]))
            ->assertOk()
            ->assertSee('data-scan-camera', false)
            ->assertDontSee('data-scan-front', false)
            ->assertDontSee('data-scan-back', false)
            ->assertSee('data-target="#document"', false)
            ->assertSee('data-target-back="#document_back"', false)
            ->assertSee('data-scan-side-label', false)
            ->assertSee('data-scan-review-panel', false)
            ->assertSee('data-scan-retake', false)
            ->assertSee('data-scan-next', false)
            ->assertSee('Retake')
            ->assertSee('Taking photo of:')
            ->assertSee('name="document"', false)
            ->assertSee('name="document_back"', false);
    }

    public function test_intermediate_steps_keep_details_and_uploads_out_of_application_tables(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'phone' => '+639171111111',
            'email' => null,
        ]);
        Storage::fake('supabase');

        $this->saveDetails($citizen, ['email' => 'new-citizen@example.test']);
        $this->assertDatabaseMissing('citizen_profiles', ['user_id' => $citizen->id]);
        $this->assertSame('+639171111111', $citizen->fresh()->phone);
        $this->assertNull($citizen->fresh()->email);

        $this->saveId($citizen);
        $this->saveSelfie($citizen);

        $draft = session('identity_verification.draft');
        $this->assertDatabaseCount('identity_verifications', 0);
        $this->assertSame('+639181234567', $draft['details']['phone']);
        Storage::disk('supabase')->assertExists($draft['document_path']);
        Storage::disk('supabase')->assertExists($draft['document_back_key']);
        Storage::disk('supabase')->assertExists($draft['selfie_path']);
    }

    public function test_final_submission_persists_the_session_draft_and_removes_staged_data(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        $citizen->update(['email' => null, 'email_verified_at' => null]);
        Storage::fake('supabase');
        Queue::fake([ProcessIdentityOcr::class]);
        config(['queue.default' => 'sync']);

        $this->saveDetails($citizen, ['email' => 'final-citizen@example.test']);
        $this->saveId($citizen);
        $this->saveSelfie($citizen);
        $stagedPaths = array_intersect_key(session('identity_verification.draft'), array_flip([
            'document_path',
            'document_back_key',
            'selfie_path',
        ]));
        $originalPhone = $citizen->phone;
        $originalBirthDate = $citizen->citizenProfile->date_of_birth->format('Y-m-d');

        $this->assertDatabaseCount('identity_verifications', 0);
        $this->assertSame($originalPhone, $citizen->fresh()->phone);
        $this->assertNull($citizen->fresh()->email);
        $this->assertSame($originalBirthDate, $citizen->citizenProfile->fresh()->date_of_birth->format('Y-m-d'));
        $this->get(route('citizen.identity-verification'))
            ->assertOk()
            ->assertViewHas('step', 4)
            ->assertSee('Teacher')
            ->assertSee('+639181234567')
            ->assertSee('final-citizen@example.test');

        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.document'), [
                'agree_truth' => '1',
                'agree_privacy' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', IdentityVerification::PENDING)
            ->assertSessionMissing('identity_verification.draft');

        $verification = $citizen->identityVerifications()->sole();
        $this->assertSame(IdentityVerification::PENDING, $verification->status);
        $this->assertNotNull($verification->submitted_at);
        $this->assertNotNull($verification->consented_at);
        $this->assertSame('+639181234567', $citizen->fresh()->phone);
        $this->assertSame('final-citizen@example.test', $citizen->fresh()->email);
        $this->assertSame('1991-04-03', $citizen->citizenProfile->fresh()->date_of_birth->format('Y-m-d'));

        foreach ($stagedPaths as $path) {
            Storage::disk('supabase')->assertMissing($path);
        }

        Storage::disk('supabase')->assertExists($verification->document_path);
        Storage::disk('supabase')->assertExists($verification->document_back_key);
        Storage::disk('supabase')->assertExists($verification->selfie_path);
        $this->assertStringStartsWith("identity-documents/{$citizen->id}/", $verification->document_path);
        $this->assertStringStartsWith("identity-documents/{$citizen->id}/", $verification->document_back_key);
        $this->assertStringStartsWith("identity-selfies/{$citizen->id}/", $verification->selfie_path);
        Queue::assertPushed(
            ProcessIdentityOcr::class,
            fn (ProcessIdentityOcr $job): bool => $job->verificationId === $verification->id
                && $job->connection === 'database'
        );
    }

    public function test_final_validation_failure_keeps_the_session_and_staged_files(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        Storage::fake('supabase');

        $this->saveId($citizen);
        $draft = session('identity_verification.draft');

        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.document'), [
                'agree_truth' => '1',
                'agree_privacy' => '1',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.selfie.0', 'Please save your selfie before submitting.')
            ->assertSessionHas('identity_verification.draft');

        $this->assertDatabaseCount('identity_verifications', 0);
        Storage::disk('supabase')->assertExists($draft['document_path']);
        Storage::disk('supabase')->assertExists($draft['document_back_key']);
    }

    public function test_invalid_selfie_upload_does_not_change_the_session_draft(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        Storage::fake('supabase');
        $this->saveId($citizen);

        $before = session('identity_verification.draft');

        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.selfie'), [
                'selfie' => UploadedFile::fake()->create('selfie.pdf', 10, 'application/pdf'),
                'selfie_source' => 'upload',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.selfie.0', 'The selfie must be a JPG or PNG photo.');

        $this->assertSame($before, session('identity_verification.draft'));
        $this->assertDatabaseCount('identity_verifications', 0);
    }

    public function test_staged_file_preview_requires_the_owning_session(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        $otherCitizen = User::factory()->create(['role' => 'citizen']);
        Storage::fake('supabase');
        $this->saveId($citizen);
        $path = session('identity_verification.draft.document_path');
        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->once()
            ->andReturn('https://storage.example/staged-id.jpg');

        $this->actingAs($citizen)
            ->get(route('citizen.identity-verification.staged-file', ['type' => 'document']))
            ->assertRedirect('https://storage.example/staged-id.jpg');

        $this->actingAs($otherCitizen)
            ->get(route('citizen.identity-verification.staged-file', ['type' => 'document']))
            ->assertNotFound();

        Storage::disk('supabase')->assertExists($path);
    }

    public function test_replacing_a_staged_id_deletes_the_previous_objects_without_creating_a_record(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        Storage::fake('supabase');
        $this->saveId($citizen);
        $previous = session('identity_verification.draft');

        $this->saveId($citizen, 'replacement.jpg');

        $current = session('identity_verification.draft');
        $this->assertNotSame($previous['document_path'], $current['document_path']);
        $this->assertNotSame($previous['document_back_key'], $current['document_back_key']);
        Storage::disk('supabase')->assertMissing($previous['document_path']);
        Storage::disk('supabase')->assertMissing($previous['document_back_key']);
        Storage::disk('supabase')->assertExists($current['document_path']);
        Storage::disk('supabase')->assertExists($current['document_back_key']);
        $this->assertDatabaseCount('identity_verifications', 0);
    }

    public function test_id_step_requires_both_sides_before_staging_any_file(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        Storage::fake('supabase');

        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.id'), [
                'document_type' => 'Philippine National ID',
                'document' => UploadedFile::fake()->image('id.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document_back');

        Storage::disk('supabase')->assertEmpty();
        $this->assertDatabaseCount('identity_verifications', 0);
    }

    public function test_id_step_rejects_files_larger_than_five_megabytes(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        Storage::fake('supabase');

        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.id'), [
                'document_type' => 'Philippine National ID',
                'document' => UploadedFile::fake()->image('large-id.jpg')->size(5121),
                'document_back' => UploadedFile::fake()->image('id-back.jpg'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.document.0', 'The ID must not be larger than 5 MB.');

        Storage::disk('supabase')->assertEmpty();
        $this->assertDatabaseCount('identity_verifications', 0);
    }

    public function test_unchanged_steps_keep_the_existing_staged_files(): void
    {
        $citizen = $this->citizenWithCompleteDetails();
        Storage::fake('supabase');
        $this->saveId($citizen);
        $this->saveSelfie($citizen);
        $before = session('identity_verification.draft');

        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.id'), [
                'document_type' => 'Philippine National ID',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.selfie'), [
                'selfie_source' => 'upload',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $after = session('identity_verification.draft');
        $this->assertSame($before['document_path'], $after['document_path']);
        $this->assertSame($before['document_back_key'], $after['document_back_key']);
        $this->assertSame($before['selfie_path'], $after['selfie_path']);
        $this->assertDatabaseCount('identity_verifications', 0);
        Storage::disk('supabase')->assertExists($before['document_path']);
        Storage::disk('supabase')->assertExists($before['document_back_key']);
        Storage::disk('supabase')->assertExists($before['selfie_path']);
    }

    public function test_prune_command_deletes_only_staged_files_older_than_one_day(): void
    {
        $this->travelTo('2026-01-03 12:00:00');
        Storage::fake('supabase');
        $expiredPath = 'identity-staging/2026-01-01/1/expired.jpg';
        $currentPath = 'identity-staging/2026-01-03/1/current.jpg';
        Storage::disk('supabase')->put($expiredPath, 'old');
        Storage::disk('supabase')->put($currentPath, 'current');

        $this->artisan('identity:prune-staged-files')
            ->assertExitCode(0);

        Storage::disk('supabase')->assertMissing($expiredPath);
        Storage::disk('supabase')->assertExists($currentPath);
    }

    private function saveDetails(User $citizen, array $overrides = []): void
    {
        $this->actingAs($citizen)
            ->post(route('citizen.identity-verification.details'), $this->detailsPayload($overrides))
            ->assertRedirect(route('citizen.identity-verification', ['step' => 2]));
    }

    private function saveId(User $citizen, string $fileName = 'id.jpg'): void
    {
        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.id'), [
                'document_type' => 'Philippine National ID',
                'document' => UploadedFile::fake()->image($fileName),
                'document_back' => UploadedFile::fake()->image('back-'.$fileName),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    private function saveSelfie(User $citizen, string $fileName = 'selfie.jpg'): void
    {
        $this->actingAs($citizen)
            ->postJson(route('citizen.identity-verification.selfie'), [
                'selfie' => UploadedFile::fake()->image($fileName),
                'selfie_source' => 'upload',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    private function citizenWithCompleteDetails(): User
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'phone' => '+639171111111',
        ]);
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

        return $citizen;
    }

    private function detailsPayload(array $overrides = []): array
    {
        return array_replace([
            'phone' => '09181234567',
            'date_of_birth' => '1991-04-03',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'place_of_birth' => 'San Luis',
            'religion' => 'Catholic',
            'occupation' => 'Teacher',
            'house_number' => '12',
            'barangay' => 'San Luis',
            'city_municipality' => 'San Luis',
            'province' => 'Province',
            'zip_code' => '1234',
        ], $overrides);
    }
}
