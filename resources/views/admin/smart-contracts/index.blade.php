@extends('layouts.admin')

@section('title', 'Smart Contract Management')

@section('content')
    <div class="space-y-6">
        {{-- Show contract health and provide processing control and ledger navigation. --}}
        <x-admin.page-header label="Smart Contracts" title="Smart Contract Management"
            text="Monitor the permit registry contract and its health.">
            @if ($isAdmin && $reachable && $supportsPause && $isOwner)
                <form method="POST" action="{{ route('admin.smart-contracts.pause') }}" class="inline-flex"
                    data-confirm-label="{{ $isPaused ? 'Resume recording' : 'Pause recording' }}"
                    data-confirm-text="{{ $isPaused ? 'Resume recording new permits on the blockchain?' : 'Pause recording of new permits on the blockchain? Approvals still work, but new permits will not be recorded until you resume.' }}"
                    onsubmit="event.preventDefault(); var form = this; tConfirmation({ title: 'Confirm processing change', text: form.dataset.confirmText, confirmLabel: form.dataset.confirmLabel }).then(function (ok) { if (ok) form.submit(); }); return false;">
                    @csrf
                    <input type="hidden" name="paused" value="{{ $isPaused ? 0 : 1 }}">
                    <button type="submit"
                        class="{{ $isPaused ? 'bg-amber-600/60 hover:bg-amber-700' : 'bg-red-700/60 hover:bg-red-800' }} inline-flex min-h-11 items-center rounded-lg px-4 text-sm font-semibold text-white">
                        {{ $isPaused ? 'Resume processing' : 'Pause processing' }}
                    </button>
                </form>
            @endif

            <a href="{{ url('/admin/blockchain-ledger') }}"
                class="inline-flex min-h-11 items-center rounded-lg bg-blue-700/70 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                View ledger
            </a>

            @if ($isAdmin)
                <button type="button" data-open-deployment-modal aria-haspopup="dialog"
                    title="Register a new deployment"
                    class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-emerald-700/70 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="M12 5v14" />
                    </svg>
                    Add
                </button>
            @endif
        </x-admin.page-header>

        {{-- Surface connectivity, deployment, and processing warnings. --}}
        @if (!$reachable)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                The blockchain could not be reached. Check the RPC URL, issuer key, and BLOCKCHAIN_NODE_PATH.
            </div>
        @else
            @if (!$chainOk)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    Connected to the wrong network. Check BLOCKCHAIN_NETWORK and the RPC URL for that network.
                </div>
            @endif
            @if (!$hasCode)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    No contract was found at this address on this network. Check the contract address and RPC URL for the active network.
                </div>
            @endif
            @if ($mismatch)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    The active deployment recorded below ({{ $short($active->contract_address) }}) is not the contract the
                    system
                    is using ({{ $short($info['contract']) }}). Check the registered deployment and the contract address for the active network, then run
                    <span class="font-mono">php artisan config:clear</span>.
                </div>
            @endif
            @if ($isPaused)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <b>Recording of new permits is paused.</b> Approvals and PDFs still work, but new fingerprints are not
                    written to the blockchain until an admin resumes processing. Revoking and QR verification are not
                    affected.
                </div>
            @endif
        @endif

        {{-- Show contract, network, issuer-wallet, and processing status at a glance. --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Permit contract</p>
                <p class="my-2 text-xl font-bold text-slate-900 dark:text-slate-300">
                    PermitRegistry{{ $active ? ' v' . $active->version : '' }}
                </p>
                @if (!$reachable)
                    <x-tables.badge variant="danger">Unreachable</x-tables.badge>
                @elseif (!$hasCode)
                    <x-tables.badge variant="danger">Not found</x-tables.badge>
                @elseif ($isPaused)
                    <x-tables.badge variant="warning">Paused</x-tables.badge>
                @else
                    <x-tables.badge variant="success">Active</x-tables.badge>
                @endif
            </div>

            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Network</p>
                <p class="my-2 text-xl font-bold capitalize text-slate-900 dark:text-slate-300">
                    {{ $networkName ?? 'Unknown' }}
                </p>
                @if (!$reachable)
                    <x-tables.badge variant="warning">Unknown</x-tables.badge>
                @elseif ($chainOk)
                    <x-tables.badge variant="success">Connected, chain {{ $info['chainId'] }}</x-tables.badge>
                @else
                    <x-tables.badge variant="danger">Wrong network</x-tables.badge>
                @endif
            </div>

            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Issuer wallet gas balance</p>
                <p class="my-2 text-xl font-bold text-slate-900 dark:text-slate-300">
                    {{ $balance !== null ? number_format($balance, 4) : 'N/A' }}
                </p>
                @if ($balance === null)
                    <x-tables.badge>No data</x-tables.badge>
                @elseif ($balance < 0.01)
                    <x-tables.badge variant="warning">Low balance</x-tables.badge>
                @else
                    <x-tables.badge variant="success">Sufficient</x-tables.badge>
                @endif
            </div>

            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Processing control</p>
                <p class="my-2 text-xl font-bold text-slate-900 dark:text-slate-300">
                    @if (!$reachable)
                        Unknown
                    @elseif (!$supportsPause)
                        Unavailable
                    @elseif ($isPaused)
                        Paused
                    @else
                        Running
                    @endif
                </p>
                @if (!$reachable)
                    <x-tables.badge variant="warning">Blockchain unreachable</x-tables.badge>
                @elseif (!$supportsPause)
                    <x-tables.badge variant="warning">No pause function</x-tables.badge>
                @elseif (!$isOwner)
                    <x-tables.badge>Read only, wallet is not owner</x-tables.badge>
                @elseif ($isPaused)
                    <x-tables.badge variant="warning">New recordings on hold</x-tables.badge>
                @else
                    <x-tables.badge variant="success">Accepting new recordings</x-tables.badge>
                @endif
            </div>
        </div>

        {{-- List registered contract deployments and their current status. --}}
        <x-tables.card title="Deployed contracts" description="The rulebooks that live on the blockchain." :searchable="false"
            :per-page-options="[]">
            <x-slot:toolbar>
                <x-tables.toolbar key="contract-deployments" :search="true" search-placeholder="Search deployments..."
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Contract', 'Purpose', 'Address', 'Version', 'Status']">
                @forelse ($deployments as $d)
                    <tr>
                        <td>PermitRegistry</td>
                        <td>
                            Permit hashes, status, history
                            @if ($d->notes)
                                <span class="block text-xs text-slate-500">{{ $d->notes }}</span>
                            @endif
                        </td>
                        <td class="font-mono text-xs">
                            @if ($deploymentExplorer = config('blockchain.networks.'.($d->network === 'live' ? 'sepolia' : $d->network).'.explorer_url'))
                                <a class="text-blue-700 underline" target="_blank" rel="noopener"
                                    href="{{ rtrim($deploymentExplorer, '/') }}/address/{{ $d->contract_address }}">{{ $short($d->contract_address) }}</a>
                            @else
                                {{ $short($d->contract_address) }}
                            @endif
                        </td>
                        <td>{{ $d->version }}</td>
                        <td>
                            <x-tables.badge :variant="$d->is_active ? 'success' : 'neutral'">
                                {{ $d->is_active ? 'Active' : 'Retired' }}
                            </x-tables.badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>PermitRegistry</td>
                        <td>Permit hashes, status, history</td>
                        <td class="font-mono text-xs">{{ $short($info['contract'] ?? null) }}</td>
                        <td>N/A</td>
                        <td>
                            <x-tables.badge :variant="$hasCode ? 'success' : 'neutral'">
                                {{ $hasCode ? 'Active' : 'Unknown' }}
                            </x-tables.badge>
                        </td>
                    </tr>
                @endforelse
            </x-tables.table>
        </x-tables.card>

        {{-- List permit-review officials and provide permission management to administrators. --}}
        <x-tables.card title="Authorized officials" :description="'Staff who can approve permits. Every on-chain action is signed by the system issuer wallet (' .
            $short($info['issuer'] ?? null) .
            ').'" :searchable="false" :per-page-options="[]">
            <x-slot:toolbar>
                <x-tables.toolbar key="authorized-officials" :search="true" search-placeholder="Search officials..."
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="$isAdmin
                ? ['Name', 'Role', 'Email', 'Can do', ['label' => 'Action', 'align' => 'center']]
                : ['Name', 'Role', 'Email', 'Can do']" :empty="$officials->isEmpty()" message="No officials found."
                hint="No staff currently have permit review access.">
                @foreach ($officials as $official)
                    <tr>
                        <td><x-tables.user-cell :name="$official->name" /></td>
                        <td>
                            <x-tables.badge :variant="$official->role === 'admin' ? 'info' : 'neutral'">
                                {{ ucfirst($official->role) }}
                            </x-tables.badge>
                        </td>
                        <td>{{ $official->email ?? 'N/A' }}</td>
                        <td>Approve, Reject, Reissue</td>
                        @if ($isAdmin)
                            <td class="text-center">
                                @if ($official->role === 'staff')
                                    <x-tables.action label="Manage" icon="shield" :href="url('/admin/users/' . $official->id . '/permissions')" />
                                @else
                                    <span class="text-slate-500 dark:text-slate-400">N/A</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

        {{-- Show recent blockchain actions with status, timestamps, and transaction hashes. --}}
        <x-tables.card title="Contract activity log" description="Latest actions recorded by the system." :searchable="false"
            :per-page-options="[]">
            <x-slot:toolbar>
                <x-tables.toolbar key="contract-activity" :search="true" search-placeholder="Search activity..."
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Time', 'Function called', 'Document no.', 'Result', 'Transaction hash']" :empty="$recent->isEmpty()" message="No blockchain activity yet.">
                @foreach ($recent as [$record, $isRevoked, $isRecorded, $time, $tx])
                    <tr>
                        <td class="whitespace-nowrap">
                            {{ $time?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </td>
                        <td class="font-mono text-xs">{{ $isRevoked ? 'revoke()' : 'issue()' }}</td>
                        <td class="font-mono text-xs">{{ $record->issuedDocument?->document_number ?? 'N/A' }}</td>
                        <td>
                            @if ($record->error && !$isRecorded)
                                <x-tables.badge variant="danger">Failed</x-tables.badge>
                                <p class="mt-1 break-all text-xs text-red-700">{{ $record->error }}</p>
                            @elseif ($isRecorded)
                                <x-tables.badge :variant="$isRevoked ? 'neutral' : 'success'">
                                    {{ $isRevoked ? 'Revoked' : 'Recorded' }}
                                </x-tables.badge>
                            @else
                                <x-tables.badge variant="warning">Pending</x-tables.badge>
                            @endif
                        </td>
                        <td class="font-mono text-xs">
                            @if ($transactionUrl = $record->transactionUrl($tx))
                                <a class="text-blue-700 underline" target="_blank" rel="noopener"
                                    href="{{ $transactionUrl }}">{{ $short($tx) }}</a>
                            @else
                                {{ $short($tx) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>

    {{-- Register-deployment modal, opened by the "Add" button in the header. --}}
    @if ($isAdmin)
        @include('admin.smart-contracts.deployment')
    @endif
@endsection