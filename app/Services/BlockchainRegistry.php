<?php

namespace App\Services;

use App\Models\ContractDeployment;
use App\Models\IssuedDocument;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

class BlockchainRegistry
{
    public function issue(string $uuid, string $hash, ?string $contract = null, ?string $network = null): array
    {
        $this->assertValidDocumentId($uuid);

        return $this->submitPermitTransaction(['prepare-issue', $uuid, $hash], $contract, $network);
    }

    // Same cleanup as toDocumentId() in registry-cli.js (trim + lowercase)
    private function assertValidDocumentId(string $uuid): void
    {
        $id = strtolower(trim($uuid));

        // Empty, or only zeros and dashes (the nil UUID)
        if (trim($id, '0-') === '') {
            throw new InvalidArgumentException('The document ID cannot be empty or all zeros.');
        }

        if (! Str::isUuid($id)) {
            throw new InvalidArgumentException('The document ID must be a valid UUID.');
        }
    }

    // Cancel a permit on the blockchain (slow)
    public function revoke(string $uuid, ?string $contract = null, ?string $network = null): array
    {
        return $this->submitPermitTransaction(['prepare-revoke', $uuid], $contract, $network);
    }

    // Compare a hash with the blockchain's copy (fast)
    // $contract is the contract the permit was recorded on. Empty means the current one.
    public function verify(string $uuid, string $hash, ?string $contract = null, ?string $network = null): array
    {
        return $this->run(['verify', $uuid, $hash], $contract, $network);
    }

    public function findIssuanceTransaction(string $uuid, ?string $contract = null, ?string $network = null): array
    {
        return $this->run(['find-issuance-tx', $uuid], $contract, $network);
    }

    public function issueVersioned(string $uuid, string $hash, int $version, string $fieldsHash, ?string $contract = null, ?string $network = null): array
    {
        $this->assertValidDocumentId($uuid);

        return $this->submitPermitTransaction(['prepare-versioned', $uuid, $hash, (string) $version, $fieldsHash], $contract, $network);
    }

    private function submitPermitTransaction(array $args, ?string $contract, ?string $network): array
    {
        $document = IssuedDocument::where('document_uuid', $args[1])->firstOrFail();
        $record = $document->blockchainRecord()->firstOrCreate([], [
            'network' => $network ?? config('blockchain.network'), 'contract' => $contract ?? config('blockchain.contract'),
        ]);

        return Cache::lock('registry-issuer:'.$record->anchorNetwork(), 150)->block(2, function () use ($record, $args, $network, $contract) {
            $record->refresh();
            $revoking = $args[0] === 'prepare-revoke';
            $hash = $revoking ? 'revoke_tx_hash' : 'tx_hash';
            $signed = $revoking ? 'signed_revocation' : 'signed_transaction';
            $submitted = $revoking ? 'revoke_submitted_at' : 'submitted_at';
            if (($revoking ? $record->revoked_at : $record->recorded_at) && $record->{$hash}) {
                return ['ok' => true, 'txHash' => $record->{$hash}, 'contract' => $record->contract];
            }
            if (! $record->{$hash}) {
                $prepared = $this->run($args, $contract, $network);
                if (empty($prepared['ok'])) {
                    return $prepared;
                }
                $record->update([$hash => $prepared['txHash'], $signed => $prepared['signed'], $submitted => now(),
                    'contract' => $record->contract ?? $contract ?? config('blockchain.contract')]);
            }
            if (! $record->{$signed}) {
                return ['ok' => false, 'error' => 'Prior submission requires receipt reconciliation.'];
            }
            $result = $this->run([$revoking ? 'submit-revocation' : 'submit-signed', $record->{$hash}], $contract, $network, $record->{$signed});
            if (($result['reverted'] ?? false) || ($result['replaced'] ?? false)) {
                $record->update(['previous_attempts' => [...($record->previous_attempts ?? []), [
                    'operation' => $args[0], 'hash' => $record->{$hash}, 'submitted_at' => $record->{$submitted}, 'error' => $result['error'],
                ]], $hash => null, $signed => null, 'error' => $result['error']]);
            }

            return $result;
        });
    }

    public function lookup(string $uuid, string $contract, ?string $network, bool $versioned): array
    {
        return $this->run(['get-versioned', $uuid, $versioned ? 'strict' : 'allow-legacy'], $contract, $network);
    }

    // Emergency switch (owner only). While paused, new permits cannot be recorded.
    // Revoking and verifying keep working. Slow: waits for a block.
    public function setPaused(bool $paused): array
    {
        $deployment = ContractDeployment::active();
        if (! $deployment) {
            throw new RuntimeException('No synchronized registry.');
        }

        return Cache::lock('registry-issuer:'.$deployment->active_network, 150)->block(2, function () use ($deployment, $paused) {
            $pending = $deployment->pending_control;
            if (! $pending) {
                $prepared = $this->run([$paused ? 'pause' : 'unpause']);
                if (empty($prepared['ok'])) {
                    return $prepared;
                }
                $pending = ['paused' => $paused, 'hash' => $prepared['txHash'], 'signed' => $prepared['signed']];
                $deployment->forceFill(['pending_control' => $pending])->saveQuietly();
            }
            if ($pending['paused'] !== $paused) {
                throw new RuntimeException('Reconcile the outstanding recording control first.');
            }
            $result = $this->run(['submit-signed', $pending['hash']], $deployment->contract_address, $deployment->network, $pending['signed']);
            if (($result['reverted'] ?? false) || ($result['replaced'] ?? false)) {
                $deployment->forceFill(['pending_control' => null])->saveQuietly();
            }

            return $result + ['txHash' => $pending['hash']];
        });
    }

    // Health and details of the current contract, for the admin Smart Contract page.
    // Returns null when the blockchain cannot be reached.
    public function inspectDeployment(string $transaction, string $owner): array
    {
        return $this->run(['deployment', $transaction, $owner]);
    }

    public function contractInfo(): ?array
    {
        return Cache::remember('blockchain.info', 30, function () {
            try {
                $info = $this->run(['info']);
            } catch (\Throwable $e) {
                report($e);

                return null;
            }

            // The CLI answers {"ok":false,...} on failure. Never cache that.
            return ($info['ok'] ?? false) === true ? $info + ['checkedAt' => now()->toDateTimeString()] : null;
        });
    }

    private function run(array $args, ?string $contract = null, ?string $network = null, ?string $input = null): array
    {
        // Safety lock: tests must never use the real blockchain
        if (app()->environment('testing')) {
            throw new RuntimeException('The real blockchain cannot be used in tests.');
        }

        $process = new Process(
            [config('blockchain.node_path'), 'scripts/registry-cli.js', ...$args],
            config('blockchain.folder'),
            $this->chainSettings($args[0], $contract, $network), // the chain settings for Node
            $input,
            120 // give up after 120 seconds
        );

        try {
            $process->run();
        } catch (ExceptionInterface) {
            throw new RuntimeException('Blockchain process unavailable or timed out.');
        }

        // The script prints exactly one line of JSON
        $result = json_decode(trim($process->getOutput()), true);

        if (! is_array($result)) {
            throw new RuntimeException(
                'Blockchain script gave no valid answer.'
            );
        }

        return $result;
    }

    private function chainSettings(string $command, ?string $contract = null, ?string $network = null): array
    {
        $activeNetwork = config('blockchain.network');
        $network ??= $activeNetwork;
        $profile = $network === 'live' ? 'sepolia' : $network;
        $settings = config('blockchain.networks.'.$profile);

        if (! is_array($settings)) {
            throw new RuntimeException('Unsupported blockchain network.');
        }

        $writes = in_array($command, ['prepare-issue', 'prepare-versioned', 'submit-signed', 'prepare-revoke', 'submit-revocation', 'pause', 'unpause'], true);
        if ($writes && $profile === 'mainnet' && config('blockchain.mainnet_writes_enabled') !== true) {
            throw new RuntimeException('Ethereum mainnet writes are disabled.');
        }

        if ($network === $activeNetwork) {
            $settings = config('blockchain');
        }

        if ($writes && ! in_array($command, ['prepare-revoke', 'submit-revocation'], true)) {
            $deployment = ContractDeployment::active();
            if (! $deployment || $profile !== ContractDeployment::network()
                || strcasecmp($deployment->contract_address, $settings['contract'] ?? '') !== 0
                || ($contract && strcasecmp($deployment->contract_address, $contract) !== 0)) {
                throw new RuntimeException('Synchronize the configured deployment before writing.');
            }
        }

        return [
            'REGISTRY_MANAGED_CONFIG' => '1',
            'REGISTRY_OWNER' => $deployment->owner_address ?? false,
            'RPC_URL' => $settings['rpc_url'] ?: false,
            'REGISTRY_ADDRESS' => ($contract ?: $settings['contract']) ?: false,
            'EXPECTED_CHAIN_ID' => (string) $settings['chain_id'],
            'ALLOW_MAINNET_WRITES' => $writes && $profile === 'mainnet' ? '1' : false,
            'PRIVATE_KEY' => ($writes || in_array($command, ['info', 'deployment'], true)) ? ($settings['issuer_key'] ?: false) : false,
        ];
    }
}
