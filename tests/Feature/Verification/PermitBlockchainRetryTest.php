<?php

namespace Tests\Feature\Verification;

use App\Jobs\RecordPermitOnBlockchain;
use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\BlockchainRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermitBlockchainRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_permit_review_permission_cannot_retry_a_transaction(): void
    {
        [, $application] = $this->makeApprovedApplication();
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => ['permits.review']]);
        $this->actingAs($staff)->post(route('admin.permits.blockchain-retry', $application))->assertForbidden();
    }

    public function test_retry_records_a_permit_that_is_not_recorded_yet(): void
    {
        [$admin, $application] = $this->makeApprovedApplication();

        $this->actingAs($admin)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertRedirect(route('admin.permits.show', $application))
            ->assertSessionHas('success');

        $record = $application->issuedDocuments()->firstOrFail()->blockchainRecord;

        $this->assertNotNull($record->recorded_at);
    }

    public function test_retry_says_so_when_the_permit_is_already_recorded(): void
    {
        [$admin, $application] = $this->makeApprovedApplication();

        BlockchainRecord::create([
            'issued_document_id' => $application->issuedDocuments()->firstOrFail()->id,
            'recorded_at' => now(),
            'tx_hash' => '0xexisting',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas('error');
    }

    public function test_admin_can_recover_a_sepolia_transaction_after_mainnet_cutover(): void
    {
        [$admin, $application] = $this->makeApprovedApplication();
        $document = $application->issuedDocuments()->firstOrFail();
        config(['blockchain.network' => 'mainnet', 'blockchain.contract' => '0xmainnet']);
        BlockchainRecord::create([
            'issued_document_id' => $document->id,
            'network' => 'sepolia',
            'contract' => '0xsepolia',
            'recorded_at' => now(),
            'error' => BlockchainRecord::TX_HASH_RECOVERY_ERROR.' Retry the blockchain lookup or backfill the transaction hash.',
        ]);

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->with($document->document_uuid, $document->file_hash, '0xsepolia', 'sepolia')
            ->andReturn(['ok' => true, 'status' => 'valid']);
        $registry->shouldReceive('findIssuanceTransaction')
            ->once()
            ->with($document->document_uuid, '0xsepolia', 'sepolia')
            ->andReturn(['ok' => true, 'found' => true, 'txHash' => '0xrecovered']);

        $this->actingAs($admin)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas('success', 'The permit is now recorded on the blockchain.');

        $record = $document->fresh()->blockchainRecord;

        $this->assertSame('0xrecovered', $record->tx_hash);
        $this->assertSame('sepolia', $record->network);
        $this->assertSame('0xsepolia', $record->contract);
        $this->assertSame('https://sepolia.etherscan.io/tx/0xrecovered', $record->transactionUrl($record->tx_hash));
        $this->assertNull($record->error);
    }

    public function test_admin_is_told_when_transaction_hash_recovery_still_needs_backfill(): void
    {
        [$admin, $application] = $this->makeApprovedApplication();
        $document = $application->issuedDocuments()->firstOrFail();
        config(['blockchain.network' => 'mainnet', 'blockchain.contract' => '0xmainnet']);
        BlockchainRecord::create([
            'issued_document_id' => $document->id,
            'network' => 'sepolia',
            'contract' => '0xsepolia',
            'recorded_at' => now(),
            'error' => BlockchainRecord::TX_HASH_RECOVERY_ERROR.' Retry the blockchain lookup or backfill the transaction hash.',
        ]);

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->with($document->document_uuid, $document->file_hash, '0xsepolia', 'sepolia')
            ->andReturn(['ok' => true, 'status' => 'valid']);
        $registry->shouldReceive('findIssuanceTransaction')
            ->once()
            ->andReturn(['ok' => true, 'found' => false]);

        $this->actingAs($admin)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas(
                'error',
                'The permit is valid on the blockchain, but its transaction hash could not be recovered. Please retry or backfill it.'
            );

        $record = $document->fresh()->blockchainRecord;

        $this->assertNull($record->tx_hash);
        $this->assertStringStartsWith(BlockchainRecord::TX_HASH_RECOVERY_ERROR, $record->error);
    }

    public function test_retry_reports_a_failure_when_the_blockchain_is_down(): void
    {
        [$admin, $application] = $this->makeApprovedApplication();

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')->andReturn(['ok' => false, 'error' => 'RPC down']);

        $this->actingAs($admin)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas('error');

        $this->assertNull($application->issuedDocuments()->firstOrFail()->blockchainRecord?->recorded_at);
    }

    /** @return array{0: User, 1: ServiceRequest} */
    private function makeApprovedApplication(): array
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['permits.review'],
        ]);
        $citizen = User::factory()->create(['role' => 'citizen']);

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

        return [$admin, $application];
    }

    public function test_retry_says_queued_when_a_real_queue_is_used(): void
    {
        Queue::fake();
        config(['queue.default' => 'database']);

        [$admin, $application] = $this->makeApprovedApplication();

        $this->actingAs($admin)
            ->post(route('admin.permits.blockchain-retry', $application))
            ->assertSessionHas('registry_notice');

        Queue::assertPushed(RecordPermitOnBlockchain::class);
    }
}
