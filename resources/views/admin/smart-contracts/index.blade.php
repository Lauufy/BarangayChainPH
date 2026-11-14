@extends('layouts.admin')

@section('title', 'Smart Contract Management')

@section('content')
    @php
        $short = fn (?string $v) => $v ? substr($v, 0, 6) . '…' . substr($v, -4) : 'N/A';
        $explorerBase = $explorer ? rtrim($explorer, '/') : null;
        $isAdmin = auth()->user()->role === 'admin';

        $reachable = (bool) $info;
        $hasCode = $info['hasCode'] ?? false;
        $chainOk = $info['chainMatches'] ?? false;
        $balance = isset($info['balance']) ? (float) $info['balance'] : null;
        $supportsPause = $info['supportsPause'] ?? false;
        $isPaused = $supportsPause && ($info['paused'] ?? false) === true;
        $isOwner = ($info['isOwner'] ?? false) === true;
        $mismatch = $active && $reachable && ! empty($info['contract'])
            && strcasecmp($active->contract_address, $info['contract']) !== 0;

        $pillOk = 'bg-emerald-100 text-emerald-800';
        $pillWarn = 'bg-amber-100 text-amber-800';
        $pillBad = 'bg-red-100 text-red-800';
        $pillNeutral = 'bg-slate-100 text-slate-700';

        $card = 'rounded-xl border border-slate-200 bg-white p-5';
        $th = 'px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500';
        $td = 'px-3 py-3 text-sm text-slate-900';
        $pill = 'inline-block rounded-full px-2.5 py-1 text-xs font-semibold';
        $input = 'mt-1 block min-h-11 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700';
        $label = 'block text-sm font-medium text-slate-700';
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Smart ContractS Management</h1>
                <p class="mt-1 text-sm text-slate-600">Monitor the permit registry contract and its health.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.smart-contracts.index', ['refresh' => 1]) }}"
                    class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                    Refresh status
                </a>
                <a href="{{ url('/admin/blockchain-ledger') }}"
                    class="inline-flex min-h-11 items-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                    View ledger
                </a>
            </div>
        </div>

        {{-- Warnings --}}
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

        {{-- Status cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Permit contract</p>
                <p class="my-2 text-xl font-bold text-slate-900">
                    PermitRegistry{{ $active ? ' v' . $active->version : '' }}
                </p>
                @if (! $reachable)
                    <span class="{{ $pill }} {{ $pillBad }}">Unreachable</span>
                @elseif (! $hasCode)
                    <span class="{{ $pill }} {{ $pillBad }}">Not found</span>
                @elseif ($isPaused)
                    <span class="{{ $pill }} {{ $pillWarn }}">Paused</span>
                @else
                    <span class="{{ $pill }} {{ $pillOk }}">Active</span>
                @endif
            </div>

            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Network</p>
                <p class="my-2 text-xl font-bold capitalize text-slate-900">
                    {{ $active?->network ?? $networkName ?? ($info ? 'Chain ' . $info['chainId'] : 'Unknown') }}
                </p>
                @if (! $reachable)
                    <span class="{{ $pill }} {{ $pillWarn }}">Unknown</span>
                @elseif ($chainOk)
                    <span class="{{ $pill }} {{ $pillOk }}">Connected, chain {{ $info['chainId'] }}</span>
                @else
                    <span class="{{ $pill }} {{ $pillBad }}">Wrong network</span>
                @endif
            </div>

            <div class="{{ $card }}">
                <p class="text-xs text-slate-500">Issuer wallet gas balance</p>
                <p class="my-2 text-xl font-bold text-slate-900">
                    {{ $balance !== null ? number_format($balance, 4) : 'N/A' }}
                </p>
                @if ($balance === null)
                    <span class="{{ $pill }} {{ $pillNeutral }}">No data</span>
                @elseif ($balance < 0.01)
                    <span class="{{ $pill }} {{ $pillWarn }}">Low balance</span>
                @else
                    <span class="{{ $pill }} {{ $pillOk }}">Sufficient</span>
                @endif
            </div>
        </div>

        {{-- Processing control (admin only) --}}
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
                        <span class="{{ $pill }} {{ $isPaused ? $pillWarn : $pillOk }}">
                            {{ $isPaused ? 'Paused' : 'Running' }}
                        </span>

                        <form method="POST" action="{{ route('admin.smart-contracts.pause') }}"
                            onsubmit="return confirm('{{ $isPaused ? 'Resume recording new permits on the blockchain?' : 'Pause recording of new permits on the blockchain?' }}')">
                            @csrf
                            <input type="hidden" name="paused" value="{{ $isPaused ? 0 : 1 }}">
                            <button type="submit"
                                class="inline-flex min-h-11 items-center rounded-lg px-4 text-sm font-semibold text-white {{ $isPaused ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-red-700 hover:bg-red-800' }}">
                                {{ $isPaused ? 'Resume processing' : 'Pause processing' }}
                            </button>
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

        {{-- Deployed contracts --}}
        <section class="{{ $card }}">
            <h2 class="text-lg font-semibold text-slate-900">Deployed contracts</h2>
            <p class="mb-3 mt-1 text-sm text-slate-600">The rulebooks that live on the blockchain.</p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-160 border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="{{ $th }}">Contract</th>
                            <th class="{{ $th }}">Purpose</th>
                            <th class="{{ $th }}">Address</th>
                            <th class="{{ $th }}">Version</th>
                            <th class="{{ $th }}">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deployments as $d)
                            <tr class="border-b border-slate-100">
                                <td class="{{ $td }} font-semibold">PermitRegistry</td>
                                <td class="{{ $td }}">
                                    Permit hashes, status, history
                                    @if ($d->notes)
                                        <span class="block text-xs text-slate-500">{{ $d->notes }}</span>
                                    @endif
                                </td>
                                <td class="{{ $td }} font-mono text-xs">
                                    @if ($explorerBase)
                                        <a class="text-blue-700 underline" target="_blank" rel="noopener"
                                            href="{{ $explorerBase }}/address/{{ $d->contract_address }}">{{ $short($d->contract_address) }}</a>
                                    @else
                                        {{ $short($d->contract_address) }}
                                    @endif
                                </td>
                                <td class="{{ $td }}">{{ $d->version }}</td>
                                <td class="{{ $td }}">
                                    <span class="{{ $pill }} {{ $d->is_active ? $pillOk : $pillNeutral }}">
                                        {{ $d->is_active ? 'Active' : 'Retired' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-b border-slate-100">
                                <td class="{{ $td }} font-semibold">PermitRegistry</td>
                                <td class="{{ $td }}">Permit hashes, status, history</td>
                                <td class="{{ $td }} font-mono text-xs">{{ $short($info['contract'] ?? null) }}</td>
                                <td class="{{ $td }}">N/A</td>
                                <td class="{{ $td }}">
                                    <span class="{{ $pill }} {{ $hasCode ? $pillOk : $pillNeutral }}">
                                        {{ $hasCode ? 'Active' : 'Unknown' }}
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Register deployment (admin only) --}}
        @if ($isAdmin)
            <section class="{{ $card }}">
                <h2 class="text-lg font-semibold text-slate-900">Register a new deployment</h2>
                <p class="mb-4 mt-1 text-sm text-slate-600">
                    Deploy the new contract version with Hardhat first, then record it here. This only saves the record.
                    It does not change which contract the system uses. That is set by BLOCKCHAIN_CONTRACT in .env.
                </p>

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.smart-contracts.deployments.store') }}"
                    class="grid gap-4 sm:grid-cols-2">
                    @csrf

                    <div>
                        <label for="version" class="{{ $label }}">Version</label>
                        <input id="version" name="version" type="text" maxlength="20" required
                            value="{{ old('version') }}" placeholder="2.0" class="{{ $input }}">
                    </div>

                    <div>
                        <label for="network" class="{{ $label }}">Network</label>
                        <input id="network" name="network" type="text" maxlength="40" required
                            value="{{ old('network', $networkName) }}" class="{{ $input }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="contract_address" class="{{ $label }}">Contract address</label>
                        <input id="contract_address" name="contract_address" type="text" required
                            value="{{ old('contract_address') }}" placeholder="0x..." class="{{ $input }} font-mono">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="deploy_tx_hash" class="{{ $label }}">Deployment transaction hash (optional)</label>
                        <input id="deploy_tx_hash" name="deploy_tx_hash" type="text"
                            value="{{ old('deploy_tx_hash') }}" placeholder="0x..." class="{{ $input }} font-mono">
                    </div>

                    <div>
                        <label for="deployed_at" class="{{ $label }}">Deployed on (optional)</label>
                        <input id="deployed_at" name="deployed_at" type="date" max="{{ now()->toDateString() }}"
                            value="{{ old('deployed_at') }}" class="{{ $input }}">
                    </div>

                    <div>
                        <label for="notes" class="{{ $label }}">Notes (optional)</label>
                        <input id="notes" name="notes" type="text" maxlength="500"
                            value="{{ old('notes') }}" placeholder="What changed in this version" class="{{ $input }}">
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input id="make_active" name="make_active" type="checkbox" value="1"
                            @checked(old('make_active')) class="h-5 w-5 rounded border-slate-300">
                        <label for="make_active" class="text-sm text-slate-700">
                            Mark as the active contract (retires the current one)
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit"
                            class="inline-flex min-h-11 items-center rounded-lg bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800">
                            Record deployment
                        </button>
                    </div>
                </form>
            </section>
        @endif

        {{-- Authorized officials --}}
        <section class="{{ $card }}">
            <h2 class="text-lg font-semibold text-slate-900">Authorized officials</h2>
            <p class="mb-3 mt-1 text-sm text-slate-600">
                Staff who can approve permits. Every on-chain action is signed by the system issuer wallet
                ({{ $short($info['issuer'] ?? null) }}).
            </p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-160 border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="{{ $th }}">Name</th>
                            <th class="{{ $th }}">Role</th>
                            <th class="{{ $th }}">Email</th>
                            <th class="{{ $th }}">Can do</th>
                            @if ($isAdmin)
                                <th class="{{ $th }}">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($officials as $official)
                            <tr class="border-b border-slate-100">
                                <td class="{{ $td }} font-semibold">{{ $official->name }}</td>
                                <td class="{{ $td }} capitalize">{{ $official->role }}</td>
                                <td class="{{ $td }}">{{ $official->email ?? 'N/A' }}</td>
                                <td class="{{ $td }}">Approve, Reject, Reissue</td>
                                @if ($isAdmin && $official->role === 'staff')
                                    <td class="{{ $td }}">
                                        <a class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50"
                                            href="{{ url('/admin/users/' . $official->id . '/permissions') }}">Manage</a>
                                    </td>
                                @elseif ($isAdmin)
                                    <td class="{{ $td }} text-slate-500">N/A</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td class="{{ $td }} text-slate-500" colspan="5">No officials found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Workflow --}}
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

        {{-- Activity log --}}
        <section class="{{ $card }}">
            <h2 class="text-lg font-semibold text-slate-900">Contract activity log</h2>
            <p class="mb-3 mt-1 text-sm text-slate-600">Latest actions recorded by the system.</p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-160 border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="{{ $th }}">Time</th>
                            <th class="{{ $th }}">Function called</th>
                            <th class="{{ $th }}">Document no.</th>
                            <th class="{{ $th }}">Result</th>
                            <th class="{{ $th }}">Transaction hash</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $record)
                            @php
                                $isRevoked = (bool) $record->revoked_at;
                                $isRecorded = (bool) $record->recorded_at;
                                $time = $record->revoked_at ?? $record->recorded_at ?? $record->created_at;
                                $tx = $isRevoked ? $record->revoke_tx_hash : $record->tx_hash;
                            @endphp
                            <tr class="border-b border-slate-100">
                                <td class="{{ $td }} whitespace-nowrap">
                                    {{ $time?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                </td>
                                <td class="{{ $td }} font-mono text-xs">{{ $isRevoked ? 'revoke()' : 'issue()' }}</td>
                                <td class="{{ $td }} font-mono text-xs">{{ $record->issuedDocument?->document_number ?? 'N/A' }}</td>
                                <td class="{{ $td }}">
                                    @if ($record->error && ! $isRecorded)
                                        <span class="{{ $pill }} {{ $pillBad }}">Failed</span>
                                        <p class="mt-1 break-all text-xs text-red-700">{{ $record->error }}</p>
                                    @elseif ($isRecorded)
                                        <span class="{{ $pill }} {{ $pillOk }}">{{ $isRevoked ? 'Revoked' : 'Recorded' }}</span>
                                    @else
                                        <span class="{{ $pill }} {{ $pillNeutral }}">Pending</span>
                                    @endif
                                </td>
                                <td class="{{ $td }} font-mono text-xs">
                                    @if ($tx && $explorerBase)
                                        <a class="text-blue-700 underline" target="_blank" rel="noopener"
                                            href="{{ $explorerBase }}/tx/{{ $tx }}">{{ $short($tx) }}</a>
                                    @else
                                        {{ $short($tx) }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="{{ $td }} text-slate-500" colspan="5">No blockchain activity yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
@endsection