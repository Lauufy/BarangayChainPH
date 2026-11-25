<?php

namespace Tests\Feature\Permit;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\PermitCorrectionsRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PermitResubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_and_correction_round_trip_preserves_reference_and_records_changes(): void
    {
        Notification::fake();
        Storage::fake('supabase');
        $application = ServiceRequest::factory()->create(['service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY]);
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $application->user;
        $old = UploadedFile::fake()->image('proof.jpg', 10, 10);
        $path = $old->store('service-requirements', 'supabase');
        $document = $application->documents()->create([
            'requirement_key' => 'valid_id', 'label' => 'Government ID', 'disk' => 'supabase', 'path' => $path,
            'original_name' => 'proof.jpg', 'mime_type' => 'image/jpeg', 'size' => $old->getSize(),
        ]);
        $url = route('admin.permits.resubmission', $application);
        $this->actingAs($admin)->post($url)->assertSessionHasErrors('flags');
        $this->post($url, ['flags' => ['address']])->assertSessionHasErrors('notes.address');
        $this->get(route('admin.permits.show', $application))->assertSee('Request Resubmission');
        $this->post($url, [
            'flags' => ['address', 'file_valid_id'],
            'notes' => ['address' => 'Add the street number.', 'file_valid_id' => 'Upload a clearer photo.'],
            'message' => 'Please check both items.',
        ])->assertSessionHasNoErrors();
        $revision = $application->revisions()->sole();
        $edit = route('citizen.resubmissions.edit', [$application, 'revision' => $revision->id]);
        Notification::assertSentTo($owner, PermitCorrectionsRequested::class, function ($notice) use ($owner, $edit, $application) {
            $mail = $notice->toMail($owner);

            return $mail->actionUrl === $edit && str_contains(implode(' ', $mail->introLines), $application->reference_number)
                && str_contains(implode(' ', $mail->introLines), 'Upload a clearer photo.');
        });
        $this->assertSame(ServiceRequest::RESUBMISSION_REQUIRED, $application->fresh()->status);
        $this->actingAs(User::factory()->create(['role' => 'citizen']))->get($edit)->assertNotFound();
        $submit = route('citizen.resubmissions.submit', $application);
        $this->post($submit)->assertNotFound();
        $this->actingAs($owner)->get($edit)->assertOk()->assertSee('Add the street number.');
        $payload = ['revision' => $revision->id, 'corrections' => ['address' => '14 Rizal Street', 'file_valid_id' => $old]];
        $this->post($submit, $payload)->assertSessionHasErrors('corrections.file_valid_id');
        $this->assertNotSame('14 Rizal Street', $application->fresh()->address);
        $payload['corrections']['file_valid_id'] = UploadedFile::fake()->image('proof.jpg', 30, 30);
        $this->post($submit, $payload)->assertSessionHasNoErrors()->assertRedirect(route('citizen.applications.show', $application));
        $this->assertSame(ServiceRequest::PENDING, $application->fresh()->status);
        $this->assertSame($application->reference_number, $application->fresh()->reference_number);
        $this->assertSame($application->address, $revision->fresh()->items['address']['old']);
        $this->assertSame('14 Rizal Street', $revision->fresh()->items['address']['new']);
        $this->assertSame($admin->id, $revision->requested_by);
        Storage::disk('supabase')->assertExists($document->fresh()->path);
        Storage::disk('supabase')->assertMissing($path);
        $this->get($edit)->assertRedirect(route('citizen.applications.show', $application));
        $this->actingAs($admin)->get(route('admin.permits.show', $application))->assertOk()->assertSee('14 Rizal Street')->assertSee('Resubmission history');
        $this->post($url, ['flags' => ['address'], 'notes' => ['address' => 'Include the unit.']])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get($edit)->assertRedirect();
        $this->post($submit, $payload)->assertRedirect()->assertSessionHas('warning');
        $this->assertSame(ServiceRequest::RESUBMISSION_REQUIRED, $application->fresh()->status);
    }

    public function test_correction_mail_is_queued_and_queue_failure_preserves_the_decision(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $permit = ServiceRequest::factory()->create();
        $data = ['flags' => ['applicant_name'], 'notes' => ['applicant_name' => 'Correct the spelling of your surname.']];
        $this->actingAs($admin)->post(route('admin.permits.resubmission', $permit), $data)->assertSessionHasNoErrors();
        $link = route('citizen.resubmissions.edit', [$permit, 'revision' => $permit->revisions()->sole()->id]);
        foreach (['database', 'mail'] as $channel) {
            Queue::assertPushed(SendQueuedNotifications::class, function ($job) use ($permit, $link, $channel) {
                if (! $job->notification instanceof PermitCorrectionsRequested || $job->channels !== [$channel]) {
                    return false;
                }

                $queued = unserialize(serialize($job));
                if ($channel === 'mail') {
                    return $queued->notification->toMail($permit->user)->actionUrl === $link;
                }

                app()->call([$queued, 'handle']);

                return $permit->user->notifications()->sole()->data['url'] === $link;
            });
        }

        $otherPermit = ServiceRequest::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail queue unavailable'));
        $this->post(route('admin.permits.resubmission', $otherPermit), $data)->assertSessionHasNoErrors();
        $this->assertSame(ServiceRequest::RESUBMISSION_REQUIRED, $otherPermit->fresh()->status);
        $this->assertSame($admin->id, $otherPermit->revisions()->sole()->requested_by);
    }

    public function test_only_flagged_changed_answers_can_be_submitted(): void
    {
        Notification::fake();
        $application = ServiceRequest::factory()->create();
        $url = route('admin.permits.resubmission', $application);
        $this->actingAs($application->user)->post($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'staff', 'permissions' => []]))->post($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post($url, [
            'flags' => ['purpose'], 'notes' => ['purpose' => 'Specify the transaction.'],
        ])->assertSessionHasNoErrors();
        $revision = $application->revisions()->sole();
        $submit = route('citizen.resubmissions.submit', $application);
        $this->actingAs($application->user)->post($submit, [
            'revision' => $revision->id, 'corrections' => ['purpose' => $application->purpose],
        ])->assertSessionHasErrors('corrections.purpose');
        $this->post($submit, [
            'revision' => $revision->id, 'corrections' => ['purpose' => 'Employment', 'address' => 'Different'],
        ])->assertSessionHasErrors('corrections');
        $this->assertNull($revision->fresh()->submitted_at);
    }
}
