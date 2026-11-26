<?php

namespace App\Jobs;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Services\BlockchainRegistry;
use App\Services\PermitFields;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** Writes an issued permit's ID and SHA-256 hash to the blockchain. */
class RecordPermitOnBlockchain implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;        // try up to 3 times

    public int $timeout = 120;    // each try may take up to 120 seconds

    public array $backoff = [30, 120]; // wait 30s, then 120s between tries

    // We pass only the row's ID. The job loads fresh data when it runs.
    public function __construct(public int $issuedDocumentId) {}

    public int $uniqueFor = 900;

    public function uniqueId(): string
    {
        return (string) $this->issuedDocumentId;
    }

    public function handle(BlockchainRegistry $registry): void
    {
        Cache::lock('permit-issuance:'.$this->issuedDocumentId, 300)->get(fn () => $this->record($registry));
    }

    private function record(BlockchainRegistry $registry): void
    {
        $document = IssuedDocument::with('blockchainRecord')->find($this->issuedDocumentId);

        // Row gone, revoked, or already recorded: nothing to do
        if (
            ! $document
            || ($document->revoked_at && $document->canonical_fields === null)
            || ($document->blockchainRecord?->recorded_at && filled($document->blockchainRecord?->tx_hash))
        ) {
            return;
        }

        // Safety check for retries: an earlier try may have succeeded
        // even though its answer never reached us.
        $record = $document->blockchainRecord;
        $network = $record?->anchorNetwork();
        $contract = $record?->contract ?: ($network
            ? config('blockchain.networks.'.$network.'.contract')
            : config('blockchain.contract'));
        $target = $record ? [$contract, $network] : [];
        $fieldsHash = $document->canonical_fields === null ? null : PermitFields::commitment($document->canonical_fields);
        $existing = $fieldsHash === null
            ? $registry->verify($document->document_uuid, $document->file_hash, ...$target)
            : $registry->lookup($document->document_uuid, $contract ?? '', $network, true);

        if (
            $record && ! $record->recorded_at && blank($record->tx_hash)
            && ($existing['code'] ?? null) === 'legacy_registry'
            && ($existing['exists'] ?? null) === false
        ) {
            $profile = $network === 'live' ? 'sepolia' : $network;
            $configuredContract = config('blockchain.networks.'.$profile.'.contract');
            if (filled($configuredContract) && strcasecmp($configuredContract, $contract ?? '') !== 0) {
                $existing = $registry->lookup($document->document_uuid, $configuredContract, $network, true);
                if (($existing['ok'] ?? false) === true) {
                    $record->update(['contract' => $configuredContract]);
                    $contract = $configuredContract;
                }
            }
        }

        if ($fieldsHash !== null && ($existing['ok'] ?? false)) {
            $existing['status'] = match (true) {
                ($existing['exists'] ?? null) === false => 'not_found',
                ($existing['exists'] ?? null) !== true,
                ($existing['fileHash'] ?? null) !== '0x'.$document->file_hash,
                ($existing['fieldsHash'] ?? null) !== '0x'.$fieldsHash,
                ($existing['version'] ?? null) !== $document->version => 'mismatch',
                ($existing['revokedAt'] ?? 0) > 0 => $document->revoked_at ? 'valid' : 'mismatch',
                default => 'valid',
            };
        }

        if (! ($existing['ok'] ?? false)) {
            throw new RuntimeException('Blockchain verification failed: '.($existing['error'] ?? 'unknown'));
        }

        if (($existing['status'] ?? null) === 'valid') {
            $values = [
                'contract' => $contract,
                'signed_transaction' => null,
                'recorded_at' => $document->blockchainRecord?->recorded_at ?? now(),
            ];

            try {
                $transaction = $registry->findIssuanceTransaction(
                    $document->document_uuid,
                    $record ? $contract : null,
                    ...($network ? [$network] : [])
                );

                if (
                    ($transaction['ok'] ?? false)
                    && ($transaction['found'] ?? false)
                    && is_string($transaction['txHash'] ?? null)
                    && $transaction['txHash'] !== ''
                ) {
                    $values['tx_hash'] = $transaction['txHash'];
                } else {
                    $values['error'] = BlockchainRecord::TX_HASH_RECOVERY_ERROR
                        .' Retry the blockchain lookup or backfill the transaction hash.';
                }
            } catch (Throwable) {
                $values['error'] = BlockchainRecord::TX_HASH_RECOVERY_ERROR
                    .' Retry the blockchain lookup or backfill the transaction hash.';
            }

            if (isset($values['error'])) {
                Log::warning($values['error'], ['issued_document_id' => $document->id]);
            }

            $this->save($document, $values);

            if ($document->fresh()?->revoked_at) {
                RevokePermitOnBlockchain::dispatch($document->id)->afterCommit();
            }

            return;
        }

        if (($existing['status'] ?? null) !== 'not_found') {
            throw new RuntimeException('Blockchain document is already recorded with a different state.');
        }

        $result = $fieldsHash === null
            ? $registry->issue($document->document_uuid, $document->file_hash, ...$target)
            : $registry->issueVersioned($document->document_uuid, $document->file_hash, $document->version, $fieldsHash, $contract, $network);

        if (! ($result['ok'] ?? false)) {
            $error = is_string($result['error'] ?? null) ? $result['error'] : 'unknown';

            if (str_contains($error, 'ContractPaused')) {
                $this->save($document, [
                    'contract' => $contract,
                    'error' => 'Blockchain issue failed: '.$error,
                ]);

                return;
            }

            throw new RuntimeException('Blockchain issue failed: '.$error);
        }

        if (! is_string($result['txHash'] ?? null) || $result['txHash'] === '') {
            throw new RuntimeException('Blockchain issue returned no transaction hash.');
        }

        // The script tells us which contract it used, so we save that one
        $this->save($document, [
            'contract' => $result['contract'] ?? $contract,
            'tx_hash' => $result['txHash'],
            'recorded_at' => now(),
            'signed_transaction' => null,
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
            $values + ['network' => $document->blockchainRecord?->anchorNetwork() ?? config('blockchain.network'), 'error' => null]
        );
    }

    // Runs after all tries fail. Save the reason so you can see it later.
    public function failed(Throwable $e): void
    {
        // The permit row could be gone, so check first
        if (IssuedDocument::whereKey($this->issuedDocumentId)->exists()) {
            BlockchainRecord::updateOrCreate(
                ['issued_document_id' => $this->issuedDocumentId],
                ['error' => $e instanceof RuntimeException && ! str_contains($e->getMessage(), '://') ? mb_substr($e->getMessage(), 0, 500) : 'Blockchain processing failed. Check the worker and RPC connection.']
            );
        }
    }
}
