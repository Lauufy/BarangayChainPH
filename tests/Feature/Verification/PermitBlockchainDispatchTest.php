<?php

namespace Tests\Feature\Verification;

use App\Jobs\RecordPermitOnBlockchain;
use App\Jobs\RevokePermitOnBlockchain;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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
            'is_active' => true,
        ]);
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
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
            ->post(route('admin.permits.approve', $application))
            ->assertRedirect(route('admin.permits.show', $application));

        $firstDocument = $application->issuedDocuments()->firstOrFail();

        Queue::assertPushed(
            RecordPermitOnBlockchain::class,
            fn (RecordPermitOnBlockchain $job): bool => $job->issuedDocumentId === $firstDocument->id
        );

        $this->actingAs($staff)
            ->post(route('admin.permits.reissue', $application))
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
            fn (RevokePermitOnBlockchain $job): bool => $job->issuedDocumentId === $firstDocument->id
        );
        Queue::assertPushed(
            RecordPermitOnBlockchain::class,
            fn (RecordPermitOnBlockchain $job): bool => $job->issuedDocumentId === $secondDocument->id
        );
    }
}
