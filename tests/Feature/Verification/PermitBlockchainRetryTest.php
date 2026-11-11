<?php

namespace Tests\Feature\Verification;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\BlockchainRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Jobs\RecordPermitOnBlockchain;
use Illuminate\Support\Facades\Queue;

class PermitBlockchainRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_records_a_permit_that_is_not_recorded_yet(): void
    {
        [$staff, $application] = $this->makeApprovedApplication();

        $this->actingAs($staff)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertRedirect(route('admin.permits.show', $application))
            ->assertSessionHas('success');

        $record = $application->issuedDocuments()->firstOrFail()->blockchainRecord;

        $this->assertNotNull($record->recorded_at);
    }

    public function test_retry_says_so_when_the_permit_is_already_recorded(): void
    {
        [$staff, $application] = $this->makeApprovedApplication();

        BlockchainRecord::create([
            'issued_document_id' => $application->issuedDocuments()->firstOrFail()->id,
            'recorded_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas('error');
    }

    public function test_retry_reports_a_failure_when_the_blockchain_is_down(): void
    {
        [$staff, $application] = $this->makeApprovedApplication();

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')->andReturn(['ok' => false, 'error' => 'RPC down']);

        $this->actingAs($staff)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas('error');

        $this->assertNull($application->issuedDocuments()->firstOrFail()->blockchainRecord?->recorded_at);
    }

    /** @return array{0: User, 1: ServiceRequest} */
    private function makeApprovedApplication(): array
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['permits.review'],
            'is_active' => true,
        ]);
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);

        $application = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-'.Str::upper(Str::random(12)),
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => 'Test Applicant',
            'address' => 'Barangay San Luis',
            'age' => 22,
            'purpose' => 'Employment',
            'status' => ServiceRequest::APPROVED,
            'permit_number' => 'PERMIT-'.Str::upper(Str::random(12)),
        ]);

        IssuedDocument::create([
            'service_request_id' => $application->id,
            'document_uuid' => (string) Str::uuid(),
            'document_number' => $application->permit_number,
            'version' => 1,
            'disk' => 'local',
            'file_path' => 'issued-documents/test.pdf',
            'file_hash' => str_repeat('a', 64),
            'issued_at' => now(),
        ]);

        return [$staff, $application];
    }

        public function test_retry_says_queued_when_a_real_queue_is_used(): void
    {
        Queue::fake();
        config(['queue.default' => 'database']);

        [$staff, $application] = $this->makeApprovedApplication();

        $this->actingAs($staff)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas('success');

        Queue::assertPushed(RecordPermitOnBlockchain::class);
    }
}