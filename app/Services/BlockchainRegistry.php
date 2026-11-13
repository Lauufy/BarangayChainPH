<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class BlockchainRegistry
{
    // Write a permit's fingerprint to the blockchain (slow: waits for a block)
    public function issue(string $uuid, string $hash): array
    {
        return $this->run(['issue', $uuid, $hash]);
    }

    // Cancel a permit on the blockchain (slow)
    public function revoke(string $uuid, ?string $contract = null): array
    {
        return $this->run(['revoke', $uuid], $contract);
    }

    // Compare a hash with the blockchain's copy (fast)
    // $contract is the contract the permit was recorded on. Empty means the current one.
    public function verify(string $uuid, string $hash, ?string $contract = null): array
    {
        return $this->run(['verify', $uuid, $hash], $contract);
    }

    public function findIssuanceTransaction(string $uuid, ?string $contract = null): array
    {
        return $this->run(['find-issuance-tx', $uuid], $contract);
    }

    private function run(array $args, ?string $contract = null): array
    {
        // Safety lock: tests must never use the real blockchain
        if (app()->environment('testing')) {
            throw new RuntimeException('The real blockchain cannot be used in tests.');
        }

        $process = new Process(
            [config('blockchain.node_path'), 'scripts/registry-cli.js', ...$args],
            config('blockchain.folder'),
            $this->chainSettings($args[0], $contract), // the chain settings for Node
            null,
            120 // give up after 120 seconds
        );

        $process->run();

        // The script prints exactly one line of JSON
        $result = json_decode(trim($process->getOutput()), true);

        if (! is_array($result)) {
            throw new RuntimeException(
                'Blockchain script gave no valid answer: '.$process->getErrorOutput()
            );
        }

        return $result;
    }

    private function chainSettings(string $command, ?string $contract = null): array
    {
        $settings = [
            'RPC_URL' => config('blockchain.rpc_url'),
            'REGISTRY_ADDRESS' => $contract ?: config('blockchain.contract'),
            'EXPECTED_CHAIN_ID' => config('blockchain.chain_id'),
        ];

        // Only commands that write to the chain need the key
        if (in_array($command, ['issue', 'revoke'], true)) {
            $settings['PRIVATE_KEY'] = config('blockchain.issuer_key');
        }

        return array_filter($settings);
    }
}
