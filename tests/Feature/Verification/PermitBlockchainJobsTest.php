<?php

namespace Tests\Feature\Verification;

use App\Jobs\RecordPermitOnBlockchain;
use App\Jobs\RevokePermitOnBlockchain;
use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\BlockchainRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PermitBlockchainJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_job_saves_the_blockchain_transaction_after_success(): void
    {
        $document = $this->makeDocument();

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->with($document->document_uuid, $document->file_hash)
            ->andReturn(['ok' => true, 'status' => 'not_found']);
        $registry->shouldReceive('issue')
            ->once()
            ->with($document->document_uuid, $document->file_hash)
            ->andReturn(['ok' => true, 'txHash' => '0xissue', 'contract' => '0xcontract']);

        (new RecordPermitOnBlockchain($document->id))->handle($registry);

        $record = $document->fresh()->blockchainRecord;

        $this->assertSame('0xissue', $record->tx_hash);
        $this->assertSame('0xcontract', $record->contract);
        $this->assertSame(config('blockchain.network'), $record->network);
        $this->assertNotNull($record->recorded_at);
        $this->assertNull($record->error);
    }

    public function test_paused_contract_issue_is_logged_as_a_failure_immediately(): void
    {
        $document = $this->makeDocument();

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->with($document->document_uuid, $document->file_hash)
            ->andReturn(['ok' => true, 'status' => 'not_found']);
        $registry->shouldReceive('issue')
            ->once()
            ->with($document->document_uuid, $document->file_hash)
            ->andReturn(['ok' => false, 'error' => 'ContractPaused']);

        (new RecordPermitOnBlockchain($document->id))->handle($registry);

        $record = $document->fresh()->blockchainRecord;

        $this->assertModelExists($record);
        $this->assertSame('Blockchain issue failed: ContractPaused', $record->error);
        $this->assertSame(config('blockchain.contract'), $record->contract);
        $this->assertNull($record->recorded_at);
    }

    public function test_record_job_recovers_the_transaction_hash_when_the_permit_is_already_valid(): void
    {
        $document = $this->makeDocument();

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->andReturn(['ok' => true, 'status' => 'valid']);
        $registry->shouldReceive('findIssuanceTransaction')
            ->once()
            ->with($document->document_uuid, null)
            ->andReturn(['ok' => true, 'found' => true, 'txHash' => '0xrecovered', 'blockNumber' => 42]);

        (new RecordPermitOnBlockchain($document->id))->handle($registry);

        $record = $document->fresh()->blockchainRecord;

        $this->assertSame('0xrecovered', $record->tx_hash);
        $this->assertNotNull($record->recorded_at);
        $this->assertNull($record->error);
    }

    public function test_record_job_saves_a_retryable_warning_when_transaction_hash_recovery_fails(): void
    {
        $document = $this->makeDocument();

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->andReturn(['ok' => true, 'status' => 'valid']);
        $registry->shouldReceive('findIssuanceTransaction')
            ->once()
            ->andReturn(['ok' => true, 'found' => false]);

        (new RecordPermitOnBlockchain($document->id))->handle($registry);

        $record = $document->fresh()->blockchainRecord;

        $this->assertNull($record->tx_hash);
        $this->assertNotNull($record->recorded_at);
        $this->assertStringStartsWith(BlockchainRecord::TX_HASH_RECOVERY_ERROR, $record->error);
    }

    public function test_revoke_job_saves_the_blockchain_transaction_after_success(): void
    {
        $document = $this->makeDocument(['revoked_at' => now()]);
        BlockchainRecord::create([
            'issued_document_id' => $document->id,
            'contract' => '0xold',
            'recorded_at' => now(),
        ]);

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->with($document->document_uuid, $document->file_hash, '0xold')
            ->andReturn(['ok' => true, 'status' => 'valid']);
        $registry->shouldReceive('revoke')
            ->once()
            ->with($document->document_uuid, '0xold')
            ->andReturn(['ok' => true, 'txHash' => '0xrevoke']);

        (new RevokePermitOnBlockchain($document->id))->handle($registry);

        $record = $document->fresh()->blockchainRecord;

        $this->assertSame('0xrevoke', $record->revoke_tx_hash);
        $this->assertNotNull($record->revoked_at);
        $this->assertNull($record->error);
    }

    public function test_revoke_job_clears_errors_when_no_blockchain_record_exists(): void
    {
        $document = $this->makeDocument(['revoked_at' => now()]);
        BlockchainRecord::create([
            'issued_document_id' => $document->id,
            'error' => 'The original recording failed.',
        ]);

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->with($document->document_uuid, $document->file_hash, null)
            ->andReturn(['ok' => true, 'status' => 'not_found']);
        $registry->shouldNotReceive('revoke');

        (new RevokePermitOnBlockchain($document->id))->handle($registry);

        $record = $document->fresh()->blockchainRecord;

        $this->assertNull($record->error);
        $this->assertNull($record->revoked_at);
    }

    public function test_record_job_does_not_issue_when_verification_fails(): void
    {
        $document = $this->makeDocument();

        $registry = $this->mock(BlockchainRegistry::class);
        $registry->shouldReceive('verify')
            ->once()
            ->andReturn(['ok' => false, 'error' => 'RPC unavailable']);
        $registry->shouldNotReceive('issue');

        try {
            (new RecordPermitOnBlockchain($document->id))->handle($registry);
            $this->fail('A failed blockchain verification must not attempt issuance.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Blockchain verification failed: RPC unavailable', $exception->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeDocument(array $attributes = []): IssuedDocument
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
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

        return IssuedDocument::create(array_merge([
            'service_request_id' => $application->id,
            'document_uuid' => (string) Str::uuid(),
            'document_number' => $application->permit_number,
            'version' => 1,
            'disk' => 'local',
            'file_path' => 'issued-documents/test.pdf',
            'file_hash' => str_repeat('a', 64),
            'issued_at' => now(),
        ], $attributes));
    }
}
