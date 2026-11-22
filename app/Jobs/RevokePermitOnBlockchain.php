<?php

namespace App\Jobs;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Services\BlockchainRegistry;
use App\Support\PermitStatusCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/** Revokes a permit's blockchain record after its local document is revoked. */
class RevokePermitOnBlockchain implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 120];

    public function __construct(public int $issuedDocumentId) {}

    public function handle(BlockchainRegistry $registry): void
    {
        $document = IssuedDocument::with('blockchainRecord')->find($this->issuedDocumentId);

        if (! $document || ! $document->revoked_at || $document->blockchainRecord?->revoked_at) {
            return;
        }

        $contract = $document->blockchainRecord?->contract;

        $existing = $registry->verify($document->document_uuid, $document->file_hash, $contract);

        if (! ($existing['ok'] ?? false)) {
            throw new RuntimeException('Blockchain verification failed: '.($existing['error'] ?? 'unknown'));
        }

        // Never recorded on the chain, so there is nothing to revoke. Just clear the old error.
        if (($existing['status'] ?? null) === 'not_found') {
            BlockchainRecord::where('issued_document_id', $document->id)->update(['error' => null]);

            return;
        }

        // Already revoked on the chain: just remember it
        if (($existing['status'] ?? null) === 'revoked') {
            $this->save($document, ['revoked_at' => now()]);
            PermitStatusCache::forget($document->document_uuid);

            return;
        }

        if (! in_array($existing['status'] ?? null, ['valid', 'mismatch'], true)) {
            throw new RuntimeException('Blockchain returned an unrecognized permit status.');
        }

        $result = $registry->revoke($document->document_uuid, $contract);

        if (! ($result['ok'] ?? false)) {
            throw new RuntimeException('Blockchain revoke failed: '.($result['error'] ?? 'unknown'));
        }

        if (! is_string($result['txHash'] ?? null) || $result['txHash'] === '') {
            throw new RuntimeException('Blockchain revoke returned no transaction hash.');
        }

        $this->save($document, [
            'revoke_tx_hash' => $result['txHash'],
            'revoked_at' => now(),
        ]);
        PermitStatusCache::forget($document->document_uuid);
    }

    // Save into the permit's blockchain record (creates it if missing)
    private function save(IssuedDocument $document, array $values): void
    {
        BlockchainRecord::updateOrCreate(
            ['issued_document_id' => $document->id],
            $values + ['error' => null]
        );
    }

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
