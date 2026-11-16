@extends('layouts.admin')

@section('title', 'Smart Contract Management')

@section('content')
    <div class="space-y-6">
        {{-- Show contract health and provide refresh and ledger navigation. --}}
        <x-admin.page-header label="Smart Contracts" title="Smart ContractS Management"
            text="Monitor the permit registry contract and its health.">
                <a href="{{ route('admin.smart-contracts.index', ['refresh' => 1]) }}"
                    class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                    Refresh status
                </a>
                <a href="{{ url('/admin/blockchain-ledger') }}"
                    class="inline-flex min-h-11 items-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                    View ledger
                </a>
        </x-admin.page-header>

        {{-- Surface connectivity, deployment, and processing warnings. --}}
        @if (! $reachable)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                The blockchain could not be reached. Check BLOCKCHAIN_RPC_URL, BLOCKCHAIN_NODE_PATH and the issuer key.
            </div>
        @else
            @if (! $chainOk)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    Connected to the wrong network. Check BLOCKCHAIN_CHAIN_ID and BLOCKCHAIN_RPC_URL.
                </div>
            @endif
            @if (! $hasCode)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    No contract was found at this address on this network. Check BLOCKCHAIN_CONTRACT and BLOCKCHAIN_RPC_URL.
                </div>
            @endif
            @if ($mismatch)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    The active deployment recorded below ({{ $short($active->contract_address) }}) is not the contract the system
                    is using ({{ $short($info['contract']) }}). Update BLOCKCHAIN_CONTRACT in .env, then run
                    <span class="font-mono">php artisan config:clear</span>.
                </div>
            @endif
            @if ($isPaused)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <b>Recording of new permits is paused.</b> Approvals and PDFs still work, but new fingerprints are not
                    written to the blockchain until an admin resumes processing. Revoking and QR verification are not affected.
                </div>
            @endif
        @endif

        {{-- Show contract, network, and issuer-wallet status at a glance. --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Permit contract</p>
                <p class="my-2 text-xl font-bold text-slate-900">
                    PermitRegistry{{ $active ? ' v' . $active->version : '' }}
                </p>
                @if (! $reachable)
                    <x-tables.badge variant="danger">Unreachable</x-tables.badge>
                @elseif (! $hasCode)
                    <x-tables.badge variant="danger">Not found</x-tables.badge>
                @elseif ($isPaused)
                    <x-tables.badge variant="warning">Paused</x-tables.badge>
                @else
                    <x-tables.badge variant="success">Active</x-tables.badge>
                @endif
            </div>

            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Network</p>
                <p class="my-2 text-xl font-bold capitalize text-slate-900">
                    {{ $active?->network ?? $networkName ?? ($info ? 'Chain ' . $info['chainId'] : 'Unknown') }}
                </p>
                @if (! $reachable)
                    <x-tables.badge variant="warning">Unknown</x-tables.badge>
                @elseif ($chainOk)
                    <x-tables.badge variant="success">Connected, chain {{ $info['chainId'] }}</x-tables.badge>
                @else
                    <x-tables.badge variant="danger">Wrong network</x-tables.badge>
                @endif
            </div>

            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Issuer wallet gas balance</p>
                <p class="my-2 text-xl font-bold text-slate-900">
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
        </div>

        {{-- Allow an authorized administrator to pause or resume new permit recording. --}}
        @if ($isAdmin)
            <section class="{{ $card }}">
                <h2 class="text-lg font-semibold text-slate-900">Processing control</h2>
                <p class="mb-4 mt-1 text-sm text-slate-600">
                    An emergency switch for recording new permits on the blockchain.
                </p>

                @if (! $reachable)
                    <p class="text-sm text-slate-600">The blockchain could not be reached, so the state is unknown.</p>
                @elseif (! $supportsPause)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        The contract currently in use has no pause function. Deploy the newer contract version, update
                        BLOCKCHAIN_CONTRACT, and this control will activate.
                    </div>
                @elseif (! $isOwner)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        The system wallet is not the owner of this contract, so it cannot pause it.
                    </div>
                @else
                    <div class="flex flex-wrap items-center gap-4">
                        <x-tables.badge :variant="$isPaused ? 'warning' : 'success'">
                            {{ $isPaused ? 'Paused' : 'Running' }}
                        </x-tables.badge>

                        <form method="POST" action="{{ route('admin.smart-contracts.pause') }}"
                            data-confirm-label="{{ $isPaused ? 'Resume recording' : 'Pause recording' }}"
                            data-confirm-text="{{ $isPaused ? 'Resume recording new permits on the blockchain?' : 'Pause recording of new permits on the blockchain?' }}"
                            onsubmit="event.preventDefault(); var form = this; tConfirmation({ title: 'Confirm processing change', text: form.dataset.confirmText, confirmLabel: form.dataset.confirmLabel }).then(function (ok) { if (ok) form.submit(); }); return false;">
                            @csrf
                            <input type="hidden" name="paused" value="{{ $isPaused ? 0 : 1 }}">
                            <x-form.button type="submit" :color="$isPaused ? 'emerald' : 'red'">
                                {{ $isPaused ? 'Resume processing' : 'Pause processing' }}
                            </x-form.button>
                        </form>
                    </div>

                    <ul class="mt-4 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        <li>While paused, new permits can still be approved, but their recording on the blockchain fails.</li>
                        <li>After resuming, use the retry button on each affected permit.</li>
                        <li>Revoking documents and QR verification keep working while paused.</li>
                        <li>Each change is a blockchain transaction and may take up to a minute. It is saved in the audit log.</li>
                    </ul>
                @endif
            </section>
        @endif

        {{-- List registered contract deployments and their current status. --}}
        <x-tables.card title="Deployed contracts" description="The rulebooks that live on the blockchain."
            :searchable="false" :per-page-options="[]">
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
                            @if ($explorerBase)
                                <a class="text-blue-700 underline" target="_blank" rel="noopener"
                                    href="{{ $explorerBase }}/address/{{ $d->contract_address }}">{{ $short($d->contract_address) }}</a>
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

        {{-- Capture a deployment record without changing the configured live contract. --}}
        @if ($isAdmin)
            <section class="{{ $card }}">
                <h2 class="text-lg font-semibold text-slate-900">Register a new deployment</h2>
                <p class="mb-4 mt-1 text-sm text-slate-600">
                    Deploy the new contract version with Hardhat first, then record it here. This only saves the record.
                    It does not change which contract the system uses. That is set by BLOCKCHAIN_CONTRACT in .env.
                </p>

                <x-feedbacks.form-errors />

                <form method="POST" action="{{ route('admin.smart-contracts.deployments.store') }}"
                    class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <x-form.input name="version" label="Version" :light="true" :value="old('version')"
                            maxlength="20" required placeholder="2.0" />
                    </div>

                    <div>
                        <x-form.input name="network" label="Network" :light="true" :value="old('network', $networkName)"
                            maxlength="40" required />
                    </div>

                    <div class="sm:col-span-2">
                        <x-form.input name="contract_address" label="Contract address" :light="true"
                            :value="old('contract_address')" required placeholder="0x..." class="font-mono" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-form.input name="deploy_tx_hash" label="Deployment transaction hash (optional)" :light="true"
                            :value="old('deploy_tx_hash')" placeholder="0x..." class="font-mono" />
                    </div>

                    <div>
                        <x-form.input name="deployed_at" label="Deployed on (optional)" type="date" :light="true"
                            :value="old('deployed_at')" :max="now()->toDateString()" />
                    </div>

                    <div>
                        <x-form.input name="notes" label="Notes (optional)" :light="true" :value="old('notes')"
                            maxlength="500" placeholder="What changed in this version" />
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input id="make_active" name="make_active" type="checkbox" value="1"
                            @checked(old('make_active')) class="h-5 w-5 rounded border-slate-300">
                        <label for="make_active" class="text-sm text-slate-700">
                            Mark as the active contract (retires the current one)
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <x-form.button type="submit" color="blue">
                            Record deployment
                        </x-form.button>
                    </div>
                </form>
            </section>
        @endif

        {{-- List permit-review officials and provide permission management to administrators. --}}
        <x-tables.card title="Authorized officials"
            :description="'Staff who can approve permits. Every on-chain action is signed by the system issuer wallet (' . $short($info['issuer'] ?? null) . ').'"
            :searchable="false" :per-page-options="[]">
            <x-slot:toolbar>
                <x-tables.toolbar key="authorized-officials" :search="true" search-placeholder="Search officials..."
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table
                :heads="$isAdmin ? ['Name', 'Role', 'Email', 'Can do', ['label' => 'Action', 'align' => 'center']] : ['Name', 'Role', 'Email', 'Can do']"
                :empty="$officials->isEmpty()" message="No officials found." hint="No staff currently have permit review access.">
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
                                    <x-tables.action label="Manage" icon="shield"
                                        :href="url('/admin/users/' . $official->id . '/permissions')" />
                                @else
                                    <span class="text-slate-500 dark:text-slate-400">N/A</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

        {{-- Explain the permit lifecycle and its blockchain outcomes. --}}
        <section class="{{ $card }}">
            <h2 class="text-lg font-semibold text-slate-900">Permit workflow</h2>
            <p class="mb-4 mt-1 text-sm text-slate-600">
                Approved permits are fingerprinted and recorded on the blockchain. Reissuing revokes the previous version.
            </p>
            <div class="flex flex-wrap items-center gap-3 text-sm font-semibold">
                <span class="rounded-lg bg-indigo-50 px-4 py-3 text-indigo-800">Pending</span>
                <span class="text-slate-500">→</span>
                <span class="rounded-lg bg-indigo-50 px-4 py-3 text-indigo-800">Processing</span>
                <span class="text-slate-500">→</span>
                <span class="rounded-lg bg-indigo-50 px-4 py-3 text-indigo-800">Approved</span>
                <span class="text-slate-500">→</span>
                <span class="rounded-lg bg-emerald-100 px-4 py-3 text-emerald-800">Recorded on-chain + QR code</span>
                <span class="ml-2 text-slate-500">or</span>
                <span class="rounded-lg bg-red-100 px-4 py-3 text-red-800">Rejected</span>
            </div>
        </section>

        {{-- Show recent blockchain actions with status, timestamps, and transaction hashes. --}}
        <x-tables.card title="Contract activity log" description="Latest actions recorded by the system."
            :searchable="false" :per-page-options="[]">
            <x-slot:toolbar>
                <x-tables.toolbar key="contract-activity" :search="true" search-placeholder="Search activity..."
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Time', 'Function called', 'Document no.', 'Result', 'Transaction hash']"
                :empty="$recent->isEmpty()" message="No blockchain activity yet.">
                @foreach ($recent as [$record, $isRevoked, $isRecorded, $time, $tx])
                    <tr>
                        <td class="whitespace-nowrap">
                            {{ $time?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </td>
                        <td class="font-mono text-xs">{{ $isRevoked ? 'revoke()' : 'issue()' }}</td>
                        <td class="font-mono text-xs">{{ $record->issuedDocument?->document_number ?? 'N/A' }}</td>
                        <td>
                            @if ($record->error && ! $isRecorded)
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
                            @if ($tx && $explorerBase)
                                <a class="text-blue-700 underline" target="_blank" rel="noopener"
                                    href="{{ $explorerBase }}/tx/{{ $tx }}">{{ $short($tx) }}</a>
                            @else
                                {{ $short($tx) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

    </div>
@endsection