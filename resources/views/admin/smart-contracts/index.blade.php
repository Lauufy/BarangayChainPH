@extends('layouts.admin')
@section('title', 'Smart Contract Management')
@section('content')
    @php
        $section = 'border-t border-slate-200 pt-5 dark:border-slate-700';
        $button =
            'inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold hover:bg-slate-100 disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-800';
        $heading = 'mb-4 text-lg font-semibold';
        $address = $info['contract'] ?? config('blockchain.contract');
        $address = preg_match('/^0x[0-9a-f]{40}$/iD', $address ?? '') ? $address : null;
        $explorer = fn($net) => config('blockchain.networks.' . ($net === 'live' ? 'sepolia' : $net) . '.explorer_url');
        $link = fn($net, $kind, $value) => $explorer($net) &&
        preg_match($kind === 'tx' ? '/^0x[0-9a-f]{64}$/iD' : '/^0x[0-9a-f]{40}$/iD', $value ?? '')
            ? rtrim($explorer($net), '/') . '/' . $kind . '/' . $value
            : null;
        $paused = $info['paused'] ?? null;
        $pendingControl = $active?->pending_control['paused'] ?? null;
        $requestPause = $pendingControl ?? !$paused;
    @endphp
    <div class="admin-module space-y-7 text-slate-900 dark:text-slate-100">
        <x-admin.page-header title="Smart Contract Management" text="Manage permit recording and inspect deployment history.">
            <form method="GET" action="{{ route('admin.smart-contracts.index') }}"
                onsubmit="this.querySelector('button').textContent='Refreshing…'">
                <input type="hidden" name="refresh" value="1">
                <button class="{{ $button }}">Refresh blockchain status</button>
            </form>
        </x-admin.page-header>

        @if (session('registry_notice'))
            <p role="status">{{ session('registry_notice') }}</p>
        @endif
        @if ($problem)
            <p role="alert"
                class="rounded-lg border border-amber-400 bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ $problem }}</p>
        @endif
        <section class="{{ $section }}">
            <h2 class="{{ $heading }}">Blockchain Overview</h2>
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['Network' => ucfirst($network), 'RPC connection' => $info ? 'Connected' : 'Unavailable', 'Observed chain ID' => $info['chainId'] ?? 'Unknown', 'Last status check' => $info['checkedAt'] ?? 'Unavailable'] as $label => $value)
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                        <dd class="mt-1 font-medium">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
        <section class="{{ $section }}">
            <h2 class="{{ $heading }}">Active Smart Contract Details</h2>
            <dl class="grid gap-x-8 gap-y-4 text-sm md:grid-cols-2">
                @foreach (['Registry' => $active ? 'PermitRegistry · ' . $active->version : 'Awaiting synchronization', 'Last deployment synchronization' => $active?->synced_at ?? 'Unavailable', 'Configured contract' => $address, 'Contract owner' => $info['owner'] ?? null, 'System issuer wallet' => $info['issuer'] ?? null, 'Issuer balance' => isset($info['balance']) ? $info['balance'] . ' ETH' : null, 'Issuer authorization' => isset($info['issuerAuthorized']) ? ($info['issuerAuthorized'] ? 'Authorized' : 'Unauthorized') : null, 'Recording state' => is_bool($paused) && !$problem ? ($paused ? 'Paused' : 'Enabled') : null] as $label => $value)
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                        <dd class="mt-1 break-all">
                            @if ($url = $link($network, 'address', (string) $value))
                                <a class="font-mono underline" href="{{ $url }}">{{ $value }}</a>
                            @else
                                {{ $value ?? 'Unknown' }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
        <section class="{{ $section }}">
            <h2 class="{{ $heading }}">Operational Controls</h2>
            @if ($isAdmin)
                <div class="flex flex-wrap gap-3">
                    @foreach (['pause' => isset($pendingControl) ? 'Recover ' . ($requestPause ? 'pause' : 'resume') . ' confirmation' : ($requestPause ? 'Pause blockchain recording' : 'Resume blockchain recording'), 'retry' => 'Retry failed transactions', 'recover' => 'Recover pending transactions'] as $operation => $label)
                        <form method="POST"
                            action="{{ $operation === 'pause' ? route('admin.smart-contracts.pause') : route('admin.smart-contracts.recovery', $operation) }}"
                            data-confirm-label="{{ $label }}"
                            onsubmit="event.preventDefault(); const form=this; tConfirmation({ title: form.dataset.confirmLabel, text: form.dataset.message, confirmLabel: 'Confirm' }).then(ok => { if(ok){ form.querySelector('button').disabled=true; form.querySelector('button').textContent='Processing…'; form.submit(); } });"
                            data-message="{{ $operation === 'pause' ? (!$requestPause ? 'Resume issuance and queue permits held during the pause?' : 'Pause new issuance? Existing permits can still be verified and revoked.') : 'Reconcile eligible permits with the registry and retry unconfirmed issuance?' }}">
                            @csrf
                            @if ($operation === 'pause')
                                <input type="hidden" name="paused" value="{{ $requestPause ? 1 : 0 }}">
                            @endif
                            <button class="{{ $button }}" @disabled(
                                $problem ||
                                    ($operation === 'pause' ? empty($info['isOwner']) : (empty($info['issuerAuthorized']) && empty($info['isOwner']))))>{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
                @if (!$problem && empty($info['isOwner']))
                    <p class="mt-2 text-sm">The contract owner controls pause and resume.</p>
                @endif
            @else
                <p class="text-sm">Your role has monitoring access.</p>
            @endif
            @if (auth()->user()->hasPermission('audit.view'))
                <a class="mt-3 inline-block text-sm underline" href="{{ route('admin.audit-logs.index', ['action' => 'registry_operations']) }}">View audit
                    logs</a>
            @endif
        </section>
        <section class="{{ $section }}">
            <h2 class="{{ $heading }}">Contract Deployment History</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            @foreach (['Version', 'Network', 'Contract', 'Creation transaction', 'Deployed'] as $col)
                                <th class="p-2">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deployments as $row)
                            <tr class="border-t border-slate-200 dark:border-slate-700">
                                <td class="p-2">{{ $row->version }}</td>
                                <td class="p-2">{{ $row->network }}</td>
                                @foreach (['address' => $row->contract_address, 'tx' => $row->deploy_tx_hash] as $kind => $value)
                                    <td class="p-2 font-mono">
                                        @if ($url = $link($row->network, $kind, $value))
                                            <a class="underline" title="{{ $value }}"
                                                href="{{ $url }}">{{ substr($value, 0, 8) }}…{{ substr($value, -6) }}</a>
                                        @else
                                            {{ $value ?? 'Unavailable' }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="p-2">{{ $row->deployed_at?->toDateString() ?? 'Unknown' }}</td>
                            </tr>
                        @empty <tr>
                                <td class="p-3" colspan="5">No deployments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>{{ $deployments->links() }}
        </section>
        <section class="{{ $section }}">
            <h2 class="{{ $heading }}">Blockchain Transaction Management</h2>
            <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
                <label class="grid gap-1 text-sm">Permit number or transaction hash<input name="q" maxlength="100"
                        value="{{ request('q') }}" class="rounded border border-slate-400 bg-transparent p-2"></label>
                <label class="grid gap-1 text-sm">Recording state<select name="state"
                        class="rounded border border-slate-400 bg-white p-2 dark:bg-slate-900">
                        <option value="">All states</option>
                        @foreach (['pending', 'submitted', 'confirmed', 'failed', 'paused', 'revoked'] as $state)
                            <option @selected(request('state') === $state) value="{{ $state }}">{{ ucfirst($state) }}
                            </option>
                        @endforeach
                    </select></label><button class="{{ $button }}">Filter</button>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            @foreach (['Permit', 'State', 'Network', 'Transaction', 'Last update'] as $col)
                                <th class="p-2">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $record)
                            @php($hash = $record->revoke_tx_hash ?? $record->tx_hash)
                            <tr class="border-t border-slate-200 dark:border-slate-700">
                                <td class="p-2">{{ $record->issuedDocument?->document_number }}</td>
                                <td class="p-2">{{ ucfirst($record->issuanceState()) }} @if ($message = $record->operationMessage())<small class="block">{{ $message }}</small>@endif</td>
                                <td class="p-2">{{ $record->anchorNetwork() }}</td>
                                <td class="p-2">
                                    @if ($url = $link($record->anchorNetwork(), 'tx', $hash))
                                        <a class="font-mono underline" href="{{ $url }}"
                                            title="{{ $hash }}">{{ substr($hash, 0, 8) }}…{{ substr($hash, -6) }}</a>
                                    @else
                                        Unavailable
                                    @endif
                                </td>
                                <td class="p-2">{{ $record->updated_at }}</td>
                            </tr>
                        @empty <tr>
                                <td class="p-3" colspan="5">No matching transactions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>{{ $transactions->links() }}
        </section>
    </div>
@endsection
