<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;

class BlockchainRegistry
{
    public function issue(string $uuid, string $hash, ?string $contract = null, ?string $network = null): array
    {
        $this->assertValidDocumentId($uuid);

        return $this->run(['issue', $uuid, $hash], $contract, $network);
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
        return $this->run(['revoke', $uuid], $contract, $network);
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

    // Emergency switch (owner only). While paused, new permits cannot be recorded.
    // Revoking and verifying keep working. Slow: waits for a block.
    public function setPaused(bool $paused): array
    {
        return $this->run([$paused ? 'pause' : 'unpause']);
    }

    // Health and details of the current contract, for the admin Smart Contract page.
    // Returns null when the blockchain cannot be reached.
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
            return ($info['ok'] ?? false) === true ? $info : null;
        });
    }

    private function run(array $args, ?string $contract = null, ?string $network = null): array
    {
        // Safety lock: tests must never use the real blockchain
        if (app()->environment('testing')) {
            throw new RuntimeException('The real blockchain cannot be used in tests.');
        }

        $process = new Process(
            [config('blockchain.node_path'), 'scripts/registry-cli.js', ...$args],
            config('blockchain.folder'),
            $this->chainSettings($args[0], $contract, $network), // the chain settings for Node
            null,
            120 // give up after 120 seconds
        );

        $process->run();

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

        $writes = in_array($command, ['issue', 'revoke', 'pause', 'unpause'], true);
        if ($writes && $profile === 'mainnet' && config('blockchain.mainnet_writes_enabled') !== true) {
            throw new RuntimeException('Ethereum mainnet writes are disabled.');
        }

        if ($network === $activeNetwork) {
            $settings = config('blockchain');
        }

        return [
            'REGISTRY_MANAGED_CONFIG' => '1',
            'RPC_URL' => $settings['rpc_url'] ?: false,
            'REGISTRY_ADDRESS' => ($contract ?: $settings['contract']) ?: false,
            'EXPECTED_CHAIN_ID' => (string) $settings['chain_id'],
            'ALLOW_MAINNET_WRITES' => $writes && $profile === 'mainnet' ? '1' : false,
            'PRIVATE_KEY' => ($writes || $command === 'info') ? ($settings['issuer_key'] ?: false) : false,
        ];
    }
}
