<?php

namespace Tests\Feature\Verification;

use App\Jobs\RecordPermitOnBlockchain;
use App\Jobs\RevokePermitOnBlockchain;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Services\BlockchainRegistry;
use Tests\TestCase;

class PermitBlockchainDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_and_reissuance_queue_blockchain_record_and_revocation(): void
    {
        Queue::fake();
        Storage::fake('supabase');

        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-20261110-0001',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Test Applicant',
            'address' => 'Barangay San Luis',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($staff)
            ->post(route('admin.permits.approve', $application), [
                'expires_at' => now()->addMonths(6)->toDateString(),
            ])
            ->assertRedirect(route('admin.permits.show', $application));

        $firstDocument = $application->issuedDocuments()->firstOrFail();

        Queue::assertPushed(
            RecordPermitOnBlockchain::class,
            fn(RecordPermitOnBlockchain $job): bool => $job->issuedDocumentId === $firstDocument->id
        );

        $this->actingAs($staff)
            ->post(route('admin.permits.reissue', $application), [
                'expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertRedirect(route('admin.permits.show', $application));

        $secondDocument = $application->issuedDocuments()
            ->where('version', 2)
            ->firstOrFail();

        $this->assertNotNull($firstDocument->fresh()->revoked_at);
        $this->assertDatabaseHas('issued_documents', [
            'id' => $secondDocument->id,
            'revoked_at' => null,
        ]);

        Queue::assertPushed(
            RevokePermitOnBlockchain::class,
            fn(RevokePermitOnBlockchain $job): bool => $job->issuedDocumentId === $firstDocument->id
        );
        Queue::assertPushed(
            RecordPermitOnBlockchain::class,
            fn(RecordPermitOnBlockchain $job): bool => $job->issuedDocumentId === $secondDocument->id
        );
    }

    public function test_approval_still_works_when_the_blockchain_is_down(): void
    {
        Storage::fake('supabase');

        // The registry says the blockchain cannot be reached
        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')->andReturn(['ok' => false, 'error' => 'RPC down']);

        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-20261110-0002',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Test Applicant',
            'address' => 'Barangay San Luis',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        // This test does not fake the queue, so the job really runs (and fails)
        $this->actingAs($staff)
            ->post(route('admin.permits.approve', $application), [
                'expires_at' => now()->addMonths(6)->toDateString(),
            ])
            ->assertRedirect(route('admin.permits.show', $application))
            ->assertSessionHas('success');

        $this->assertSame(1, $application->issuedDocuments()->count());
    }

    public function test_approving_without_an_expiry_date_is_rejected(): void
    {
        Storage::fake('supabase');

        // The registry says the blockchain cannot be reached
        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')->andReturn(['ok' => false, 'error' => 'RPC down']);

        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
        ]);
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-20261110-0002',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Test Applicant',
            'address' => 'Barangay San Luis',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);

        $this->actingAs($staff)
            ->post(route('admin.permits.approve', $application))
            ->assertSessionHasErrors('expires_at');

        $this->assertSame(0, $application->issuedDocuments()->count());
    }
}
