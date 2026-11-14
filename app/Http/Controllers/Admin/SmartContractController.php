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

        return view('admin.smart-contracts.index', [
            'info' => $registry->contractInfo(),
            'deployments' => $deployments,
            'active' => $deployments->firstWhere('is_active', true),
            'officials' => $officials,
            'recent' => BlockchainRecord::with('issuedDocument')->latest('id')->take(8)->get(),
            'explorer' => config('blockchain.explorer_url'),   // check this key in config/blockchain.php
            'networkName' => config('blockchain.network'),      // and this one
        ]);
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
