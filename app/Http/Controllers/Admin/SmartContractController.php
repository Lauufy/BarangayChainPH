<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockchainRecord;
use App\Models\ContractDeployment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BlockchainRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SmartContractController extends Controller
{
    public function index(Request $request, BlockchainRegistry $registry): View|RedirectResponse
    {
        if ($request->boolean('refresh')) {
            Cache::forget('blockchain.info');

            return redirect()->route('admin.smart-contracts.index');
        }

        $deployments = ContractDeployment::latest('deployed_at')->get();

        $officials = User::whereIn('role', ['admin', 'staff'])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('permits.review'))
            ->values();

        $info = $registry->contractInfo();
        $active = $deployments->firstWhere('is_active', true);
        $recent = BlockchainRecord::with('issuedDocument')->latest('id')->take(8)->get()
            ->map(function (BlockchainRecord $record): array {
                $isRevoked = (bool) $record->revoked_at;
                $isRecorded = (bool) $record->recorded_at;

                return [
                    $record,
                    $isRevoked,
                    $isRecorded,
                    $record->revoked_at ?? $record->recorded_at ?? $record->created_at,
                    $isRevoked ? $record->revoke_tx_hash : $record->tx_hash,
                ];
            });
        $explorer = config('blockchain.explorer_url');

        return view('admin.smart-contracts.index', [
            'info' => $info,
            'deployments' => $deployments,
            'active' => $active,
            'officials' => $officials,
            'recent' => $recent,
            'explorerBase' => $explorer ? rtrim($explorer, '/') : null,
            'networkName' => config('blockchain.network'),
            'isAdmin' => $request->user()->role === 'admin',
            'reachable' => (bool) $info,
            'hasCode' => $info['hasCode'] ?? false,
            'chainOk' => $info['chainMatches'] ?? false,
            'balance' => isset($info['balance']) ? (float) $info['balance'] : null,
            'supportsPause' => $info['supportsPause'] ?? false,
            'isPaused' => ($info['supportsPause'] ?? false) && ($info['paused'] ?? false) === true,
            'isOwner' => ($info['isOwner'] ?? false) === true,
            'mismatch' => $active && $info && ! empty($info['contract'])
                && strcasecmp($active->contract_address, $info['contract']) !== 0,
            'short' => fn (?string $value): string => $this->shortAddress($value),
            'card' => 'rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900',
        ]);
    }

    private function shortAddress(?string $value): string
    {
        return $value ? substr($value, 0, 6).'…'.substr($value, -4) : 'N/A';
    }

    // Pause or resume recording of NEW permits on the blockchain (admin only, owner wallet only).
    public function togglePause(Request $request, BlockchainRegistry $registry): RedirectResponse
    {
        set_time_limit(120); // the transaction waits for a block

        $data = $request->validate(['paused' => ['required', 'boolean']]);
        $pause = (bool) $data['paused'];

        Cache::forget('blockchain.info');
        $info = $registry->contractInfo();

        if (! $info) {
            return back()->with('error', 'The blockchain could not be reached. Nothing was changed.');
        }

        if (! ($info['supportsPause'] ?? false)) {
            return back()->with('error', 'This contract version has no pause function. Deploy the newer version first.');
        }

        if (($info['isOwner'] ?? false) !== true) {
            return back()->with('error', 'The system wallet is not the contract owner, so it cannot pause the contract.');
        }

        if (($info['paused'] ?? null) === $pause) {
            return back()->with('success', 'The contract is already in that state.');
        }

        try {
            $result = $registry->setPaused($pause);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'The change could not be sent to the blockchain. Please try again.');
        }

        Cache::forget('blockchain.info');

        if (($result['ok'] ?? false) !== true) {
            return back()->with('error', 'The blockchain rejected the change: '.($result['error'] ?? 'unknown error'));
        }

        $subject = ContractDeployment::where('is_active', true)->first() ?? $request->user();

        app(AuditLogger::class)->log(
            $pause ? 'contract_paused' : 'contract_resumed',
            $subject,
            note: ($pause ? 'Paused' : 'Resumed').' new permit recording. Transaction '.($result['txHash'] ?? 'unknown').'.'
        );

        return back()->with(
            'success',
            $pause
                ? 'Recording of new permits is paused. Revoking and verification still work.'
                : 'Recording of new permits is resumed. Retry any permits that failed while paused.'
        );
    }

    // Records a contract version you deployed with Hardhat. It never touches the blockchain.
    public function storeDeployment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:20'],
            'network' => ['required', 'string', 'max:40'],
            'contract_address' => ['required', 'regex:/^0x[a-fA-F0-9]{40}$/', 'unique:contract_deployments,contract_address'],
            'deploy_tx_hash' => ['nullable', 'regex:/^0x[a-fA-F0-9]{64}$/'],
            'deployed_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'make_active' => ['nullable', 'boolean'],
        ], [
            'contract_address.regex' => 'The contract address must start with 0x followed by 40 hex characters.',
            'deploy_tx_hash.regex' => 'The transaction hash must start with 0x followed by 64 hex characters.',
        ]);

        DB::transaction(function () use ($data, $request) {
            $makeActive = $request->boolean('make_active');

            if ($makeActive) {
                // one by one so each change reaches the audit log
                ContractDeployment::where('is_active', true)->get()
                    ->each(fn (ContractDeployment $d) => $d->update(['is_active' => false]));
            }

            ContractDeployment::create([
                'version' => $data['version'],
                'network' => $data['network'],
                'contract_address' => $data['contract_address'],
                'deploy_tx_hash' => $data['deploy_tx_hash'] ?? null,
                'deployed_at' => $data['deployed_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'is_active' => $makeActive,
            ]);
        });

        return redirect()
            ->route('admin.smart-contracts.index')
            ->with('success', 'Deployment recorded. If it is the active one, update BLOCKCHAIN_CONTRACT in .env too.');
    }
}
