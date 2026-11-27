<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RecordPermitOnBlockchain;
use App\Jobs\RevokePermitOnBlockchain;
use App\Models\BlockchainRecord;
use App\Models\ContractDeployment;
use App\Services\AuditLogger;
use App\Services\BlockchainRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class SmartContractController extends Controller
{
    /** States an operator can act on with the recovery controls. */
    private const ATTENTION_STATES = ['pending', 'submitted', 'failed', 'paused'];

    public function index(Request $request, BlockchainRegistry $registry): View
    {
        if ($request->boolean('refresh')) {
            Cache::forget('blockchain.info');
        }

        $active = ContractDeployment::active();
        $info = $registry->contractInfo();
        $problem = $this->configurationError($active, $info);

        // Record counts per actionable state; the ledger handles browsing.
        $counts = BlockchainRecord::query()
            ->selectRaw(BlockchainRecord::STATE_SQL.' as state, count(*) as total')
            ->groupBy('state')
            ->pluck('total', 'state');
        $attention = collect(self::ATTENTION_STATES)
            ->mapWithKeys(fn ($state) => [$state => (int) ($counts[$state] ?? 0)])
            ->all();

        $deployments = ContractDeployment::latest('deployed_at')->paginate(8, ['*'], 'deployments')->withQueryString();

        return view('admin.smart-contracts.index', compact('info', 'active', 'problem', 'attention', 'deployments') + [
            'network' => ContractDeployment::network(),
            'isAdmin' => $request->user()->role === 'admin',
        ]);
    }

    public function togglePause(Request $request, BlockchainRegistry $registry): RedirectResponse
    {
        $request->validate(['paused' => 'required|boolean']);

        return $this->operate($registry, $request->boolean('paused') ? 'pause' : 'resume');
    }

    public function recover(string $operation, BlockchainRegistry $registry): RedirectResponse
    {
        return $this->operate($registry, $operation);
    }

    private function configurationError(?ContractDeployment $active, ?array $info): ?string
    {
        return match (true) {
            ! $info => 'Blockchain status unavailable. Check the RPC connection and registry configuration.',
            empty($info['chainMatches']), ($info['chainId'] ?? null) !== config('blockchain.chain_id') => 'Connected to the wrong chain.',
            ! preg_match('/^0x[0-9a-f]{40}$/iD', config('blockchain.contract') ?? '') => 'Registry address missing or invalid.',
            empty($info['hasCode']) => 'No contract bytecode at the configured address.',
            empty($info['issuer']) => 'Issuer wallet missing or invalid. Check the signer configuration.',
            ! $active => 'The configured deployment requires maintainer synchronization.',
            strcasecmp($active->contract_address, $info['contract'] ?? '') !== 0,
            strcasecmp($active->contract_address, config('blockchain.contract') ?? '') !== 0 => 'The configured address differs from the synchronized deployment.',
            empty($info['compatible']), empty($info['supportsPause']) => 'The registry interface could not be verified.',
            strcasecmp($active->owner_address, $info['owner'] ?? '') !== 0 => 'Registry ownership differs from the synchronized owner.',
            default => null,
        };
    }

    private function operate(BlockchainRegistry $registry, string $action): RedirectResponse
    {
        set_time_limit(180);
        $network = ContractDeployment::network();
        $audit = app(AuditLogger::class);
        $active = null;
        $context = ['network' => $network, 'contract' => config('blockchain.contract'), 'operation' => $action];
        $lock = Cache::lock('registry-management:'.$network, 300);
        $acquired = $lock->get();
        try {
            if (! $acquired) {
                throw new \DomainException('Another registry operation is in progress.');
            }
            $active = ContractDeployment::active();
            Cache::forget('blockchain.info');
            $info = $registry->contractInfo();
            if ($error = $this->configurationError($active, $info)) {
                throw new \DomainException($error);
            }
            if ($network === 'mainnet' && ! config('blockchain.mainnet_writes_enabled')) {
                throw new \DomainException('Ethereum mainnet writes are disabled.');
            }
            if (in_array($action, ['pause', 'resume'], true)) {
                $paused = $action === 'pause';
                if (empty($info['isOwner']) || ! is_bool($info['paused'] ?? null) || ($info['paused'] === $paused && ($active->pending_control['paused'] ?? null) !== $paused)) {
                    throw new \DomainException('Owner authorization and a change in recording state are required.');
                }
                $result = $registry->setPaused($paused);
                if (preg_match('/^0x[0-9a-f]{64}$/iD', $result['txHash'] ?? '')) {
                    $context['transaction'] = $result['txHash'];
                }
                if (empty($result['ok']) || ($result['paused'] ?? null) !== $paused
                    || ! preg_match('/^0x[0-9a-f]{64}$/iD', $result['txHash'] ?? '')
                    || ($result['chainId'] ?? null) !== $info['chainId']
                    || strcasecmp($result['contract'] ?? '', $active->contract_address)) {
                    throw new \DomainException('No matching confirmation was received. Refresh status before trying again.');
                }
                $audit->log($paused ? 'contract_paused' : 'contract_resumed', $active, newValues: $context + ['result' => 'confirmed']);
                if (! $paused) {
                    $this->queueRecords($active, 'resume');
                }

                $active->refresh()->forceFill(['pending_control' => null])->saveQuietly();

                return back()->with('success', $paused ? 'Blockchain recording paused.' : 'Blockchain recording resumed. Eligible paused permits were queued.');
            }
            if (empty($info['issuerAuthorized']) && empty($info['isOwner'])) {
                throw new \DomainException('Recovery requires an authorized signer.');
            }
            $count = $this->queueRecords($active, $action, ($info['paused'] ?? null) !== false || empty($info['issuerAuthorized']));
            $audit->log('contract_'.$action, $active, newValues: $context + ['result' => 'dispatched', 'count' => $count]);

            return back()->with('registry_notice', "Requested reconciliation for {$count} eligible permits.");
        } catch (Throwable $e) {
            $message = $e instanceof \DomainException ? $e->getMessage() : 'The operation could not be completed. Refresh the registry status.';
            $audit->log('contract_operation_failed', $active, newValues: $context + ['result' => $message]);

            return back()->with('error', $message);
        } finally {
            if ($acquired) {
                $lock->release();
                Cache::forget('blockchain.info');
            }
        }
    }

    private function queueRecords(ContractDeployment $active, string $action, bool $revocationsOnly = false): int
    {
        $states = match ($action) {
            'retry' => ['failed', 'paused'], 'resume' => ['paused'], default => ['pending', 'submitted'],
        };
        $count = 0;
        BlockchainRecord::whereIn('network', $active->active_network === 'sepolia' ? ['live', 'sepolia'] : [$active->active_network])
            ->where(fn ($q) => $q->whereRaw('lower(contract) = ?', [strtolower($active->contract_address)])->orWhereNull('contract'))
            ->whereRaw(BlockchainRecord::STATE_SQL.' in ('.implode(',', array_fill(0, count($states), '?')).')', $states)
            ->when($revocationsOnly, fn ($q) => $q->whereHas('issuedDocument', fn ($docs) => $docs->whereNotNull('revoked_at')))
            ->with('issuedDocument')
            ->whereHas('issuedDocument', fn ($docs) => $docs->whereNull('revoked_at')
                ->orWhere(fn ($q) => $q->whereNotNull('revoked_at')->whereHas('blockchainRecord', fn ($record) => $record->whereNotNull('recorded_at'))))
            ->chunkById(100, function ($records) use (&$count) {
                foreach ($records as $record) {
                    if ($record->issuedDocument->revoked_at) {
                        RevokePermitOnBlockchain::dispatch($record->issued_document_id);
                    } else {
                        RecordPermitOnBlockchain::dispatch($record->issued_document_id);
                    }
                    $count++;
                }
            });

        return $count;
    }
}