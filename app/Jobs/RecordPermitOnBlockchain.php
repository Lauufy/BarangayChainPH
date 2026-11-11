<?php

namespace App\Jobs;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Services\BlockchainRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/** Writes an issued permit's ID and SHA-256 hash to the blockchain. */
class RecordPermitOnBlockchain implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;        // try up to 3 times

    public int $timeout = 120;    // each try may take up to 120 seconds

    public array $backoff = [30, 120]; // wait 30s, then 120s between tries

    // We pass only the row's ID. The job loads fresh data when it runs.
    public function __construct(public int $issuedDocumentId) {}

    public function handle(BlockchainRegistry $registry): void
    {
        $document = IssuedDocument::with('blockchainRecord')->find($this->issuedDocumentId);

        // Row gone, revoked, or already recorded: nothing to do
        if (! $document || $document->revoked_at || $document->blockchainRecord?->recorded_at) {
            return;
        }

        // Safety check for retries: an earlier try may have succeeded
        // even though its answer never reached us.
        $existing = $registry->verify($document->document_uuid, $document->file_hash);

        if (! ($existing['ok'] ?? false)) {
            throw new RuntimeException('Blockchain verification failed: '.($existing['error'] ?? 'unknown'));
        }

        if (($existing['status'] ?? null) === 'valid') {
            $this->save($document, [
                'contract' => config('blockchain.contract'),
                'recorded_at' => now(),
            ]);

            return;
        }

        if (($existing['status'] ?? null) !== 'not_found') {
            throw new RuntimeException('Blockchain document is already recorded with a different state.');
        }

        // The real work: only the UUID and the hash go on the blockchain
        $result = $registry->issue($document->document_uuid, $document->file_hash);

        if (! ($result['ok'] ?? false)) {
            throw new RuntimeException('Blockchain issue failed: '.($result['error'] ?? 'unknown'));
        }

        if (! is_string($result['txHash'] ?? null) || $result['txHash'] === '') {
            throw new RuntimeException('Blockchain issue returned no transaction hash.');
        }

        // The script tells us which contract it used, so we save that one
        $this->save($document, [
            'contract' => $result['contract'] ?? config('blockchain.contract'),
            'tx_hash' => $result['txHash'],
            'recorded_at' => now(),
        ]);

        if ($document->fresh()->revoked_at) {
            RevokePermitOnBlockchain::dispatch($document->id)->afterCommit();
        }
    }

    // Save the chain details in the permit's blockchain record (creates it if missing)
    private function save(IssuedDocument $document, array $values): void
    {
        BlockchainRecord::updateOrCreate(
            ['issued_document_id' => $document->id],
            $values + ['network' => config('blockchain.network'), 'error' => null]
        );
    }

    // Runs after all tries fail. Save the reason so you can see it later.
    public function failed(Throwable $e): void
    {
        // The permit row could be gone, so check first
        if (IssuedDocument::whereKey($this->issuedDocumentId)->exists()) {
            BlockchainRecord::updateOrCreate(
                ['issued_document_id' => $this->issuedDocumentId],
                ['error' => mb_substr($e->getMessage(), 0, 1000)]
            );
        }
    }
}