<?php

namespace App\Console\Commands;

use App\Models\ContractDeployment;
use App\Services\AuditLogger;
use App\Services\BlockchainRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SyncContractDeployment extends Command
{
    protected $signature = 'blockchain:sync-deployment {transaction} {version} {owner}';

    protected $description = 'Validate the configured registry and activate its local deployment record';

    public function handle(BlockchainRegistry $registry, AuditLogger $audit): int
    {
        $network = ContractDeployment::network();
        $address = config('blockchain.contract');
        $transaction = $this->argument('transaction');
        $owner = $this->argument('owner');
        $version = $this->argument('version');
        $context = ['network' => $network, 'operator' => getenv('USERNAME') ?: (getenv('USER') ?: 'console'), 'contract' => preg_match('/^0x[0-9a-f]{40}$/iD', $address ?? '') ? $address : null,
            'transaction' => preg_match('/^0x[0-9a-f]{64}$/iD', $transaction) ? $transaction : null];

        return $audit->withinBackgroundExecution(function () use ($registry, $audit, $context, $address, $network, $transaction, $version, $owner) {
            try {
                Validator::make(compact('address', 'transaction', 'version', 'owner'), [
                    'address' => ['required', 'regex:/^0x[0-9a-f]{40}$/iD'],
                    'transaction' => ['required', 'regex:/^0x[0-9a-f]{64}$/iD'],
                    'owner' => ['required', 'regex:/^0x[0-9a-f]{40}$/iD'],
                    'version' => ['required', 'regex:/^[a-z0-9][a-z0-9._-]{0,19}$/iD'],
                ])->validate();
                Cache::lock('registry-management:'.$network, 180)->block(2, function () use ($registry, $audit, $context, $address, $network, $transaction, $version, $owner) {
                    $info = $registry->inspectDeployment($transaction, $owner);
                    if (empty($info['ok']) || empty($info['chainMatches']) || empty($info['compatible'])
                        || strcasecmp($info['contract'] ?? '', $address) || strcasecmp($info['owner'] ?? '', $owner)) {
                        throw new \RuntimeException('Registry validation failed.');
                    }
                    DB::transaction(function () use ($audit, $context, $address, $network, $transaction, $version, $owner, $info) {
                        if (ContractDeployment::active()?->pending_control) {
                            throw new \RuntimeException('Reconcile the outstanding control before synchronization.');
                        }
                        $networks = $network === 'sepolia' ? ['sepolia', 'live'] : [$network];
                        $row = ContractDeployment::whereIn('network', $networks)
                            ->whereRaw('lower(contract_address) = ?', [strtolower($address)])->first() ?? new ContractDeployment;
                        if ($row->deploy_tx_hash && strcasecmp($row->deploy_tx_hash, $transaction) !== 0) {
                            throw new \RuntimeException('The recorded creation transaction conflicts with this receipt.');
                        }
                        $activating = $row->active_network !== $network;
                        ContractDeployment::whereIn('network', $networks)
                            ->when($row->exists, fn ($query) => $query->whereKeyNot($row->getKey()))
                            ->update(['is_active' => false, 'active_network' => null]);
                        $row->fill(['network' => $network, 'contract_address' => $address, 'deploy_tx_hash' => $transaction,
                            'version' => $version, 'owner_address' => $owner, 'active_network' => $network, 'is_active' => true,
                            'deployed_at' => now()->setTimestamp($info['deployedAt']), 'synced_at' => now()])->save();
                        $details = $context + ['transaction' => $transaction, 'result' => 'validated'];
                        $audit->log('deployment_synchronized', $row, newValues: $details);
                        if ($activating) {
                            $audit->log('contract_activated', $row, newValues: $details);
                        }
                    });
                });
            } catch (Throwable) {
                $audit->log('deployment_sync_failed', null, newValues: $context + ['result' => 'failed']);
                $this->error('Synchronization failed. Check the configuration, compiled artifact, owner and creation receipt.');

                return self::FAILURE;
            }
            Cache::forget('blockchain.info');
            $this->info('Deployment validated and synchronized.');

            return self::SUCCESS;
        });
    }
}
