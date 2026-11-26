@extends('layouts.admin')

@section('title', 'Smart Contract Management')

@section('content')
    @php
        // ---- Styling ----
        $panel = 'rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900';
        $heading = 'text-base font-semibold text-slate-900 dark:text-slate-100';
        $subheading = 'mt-0.5 text-sm text-slate-500 dark:text-slate-400';
        $button =
            'inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-500) disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';
        $mono = 'font-mono text-xs text-[#17325c] hover:underline dark:text-slate-300';

        // ---- Explorer links ----
        $explorer = fn($net) => config('blockchain.networks.' . ($net === 'live' ? 'sepolia' : $net) . '.explorer_url');
        $link = fn($net, $kind, $value) => $explorer($net) &&
        preg_match($kind === 'tx' ? '/^0x[0-9a-f]{64}$/iD' : '/^0x[0-9a-f]{40}$/iD', $value ?? '')
            ? rtrim($explorer($net), '/') . '/' . $kind . '/' . $value
            : null;
        $short = fn($value) => substr($value, 0, 8) . '…' . substr($value, -6);

        // ---- Contract state ----
        $address = $info['contract'] ?? config('blockchain.contract');
        $address = preg_match('/^0x[0-9a-f]{40}$/iD', $address ?? '') ? $address : null;
        $paused = $info['paused'] ?? null;
        $pendingControl = $active?->pending_control['paused'] ?? null;
        $requestPause = $pendingControl ?? !$paused;
        $recordingState = is_bool($paused) && !$problem ? ($paused ? 'Paused' : 'Enabled') : null;

        // ---- Overview strip (one icon color; status shows in the value color) ----
        $overview = [
            [
                'label' => 'Network',
                'value' => ucfirst($network),
                'subtitle' => 'Active blockchain network',
                'valueClass' => '',
                'icon' =>
                    '<circle cx="12" cy="12" r="10" /><path d="M2 12h20" /><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />',
            ],
            [
                'label' => 'RPC Connection',
                'value' => $info ? 'Connected' : 'Unavailable',
                'subtitle' => 'Checked ' . ($info['checkedAt'] ?? 'never'),
                'valueClass' => $info ? '' : 'text-red-600 dark:text-red-400',
                'icon' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2" />',
            ],
            [
                'label' => 'Chain ID',
                'value' => $info['chainId'] ?? 'Unknown',
                'subtitle' => 'Reported by the RPC node',
                'valueClass' => '',
                'icon' =>
                    '<line x1="4" x2="20" y1="9" y2="9" /><line x1="4" x2="20" y1="15" y2="15" /><line x1="10" x2="8" y1="3" y2="21" /><line x1="16" x2="14" y1="3" y2="21" />',
            ],
            [
                'label' => 'Recording',
                'value' => $recordingState ?? 'Unknown',
                'subtitle' => $recordingState === 'Paused' ? 'New permits are on hold' : 'Permit issuance on-chain',
                'valueClass' => match ($recordingState) {
                    'Paused' => 'text-amber-600 dark:text-amber-400',
                    'Enabled' => '',
                    default => 'text-red-600 dark:text-red-400',
                },
                'icon' => '<path d="M12 2v10" /><path d="M18.4 6.6a9 9 0 1 1-12.77.04" />',
            ],
        ];

        // ---- Needs attention (counts link into the ledger) ----
        $attentionLabels = [
            'pending' => 'Pending',
            'submitted' => 'Submitted',
            'failed' => 'Failed',
            'paused' => 'Paused',
        ];
        $attentionTotal = array_sum($attention);

        // ---- Contract details ----
        $details = [
            [
                'name' => 'registry',
                'label' => 'Registry',
                'value' => $active ? 'PermitRegistry · ' . $active->version : 'Awaiting synchronization',
            ],
            [
                'name' => 'synced_at',
                'label' => 'Last deployment synchronization',
                'value' => $active?->synced_at ?? 'Unavailable',
            ],
            ['name' => 'contract', 'label' => 'Configured contract', 'value' => $address, 'type' => 'address'],
            ['name' => 'owner', 'label' => 'Contract owner', 'value' => $info['owner'] ?? null, 'type' => 'address'],
            [
                'name' => 'issuer',
                'label' => 'System issuer wallet',
                'value' => $info['issuer'] ?? null,
                'type' => 'address',
            ],
            [
                'name' => 'balance',
                'label' => 'Issuer balance',
                'value' => isset($info['balance']) ? $info['balance'] . ' ETH' : null,
            ],
            [
                'name' => 'authorization',
                'label' => 'Issuer authorization',
                'value' => isset($info['issuerAuthorized'])
                    ? ($info['issuerAuthorized']
                        ? 'Authorized'
                        : 'Unauthorized')
                    : null,
            ],
        ];

        // ---- Operational controls ----
        $canPause = !$problem && !empty($info['isOwner']);
        $canRecover = !$problem && (!empty($info['issuerAuthorized']) || !empty($info['isOwner']));
        $recoverMessage = 'Reconcile eligible permits with the registry and retry unconfirmed issuance?';

        $actions = [
            [
                'title' => $requestPause ? 'Pause recording' : 'Resume recording',
                'label' =>
                    $pendingControl !== null
                        ? 'Recover ' . ($requestPause ? 'pause' : 'resume') . ' confirmation'
                        : ($requestPause
                            ? 'Pause blockchain recording'
                            : 'Resume blockchain recording'),
                'description' => $requestPause
                    ? 'Stops new permits from being written. Existing permits can still be verified and revoked.'
                    : 'Resumes issuance and queues permits held during the pause.',
                'url' => route('admin.smart-contracts.pause'),
                'message' => $requestPause
                    ? 'Pause new issuance? Existing permits can still be verified and revoked.'
                    : 'Resume issuance and queue permits held during the pause?',
                'enabled' => $canPause,
                'pause' => true,
            ],
            [
                'title' => 'Retry failed',
                'label' => 'Retry failed transactions',
                'description' => 'Sends failed permit transactions to the blockchain again.',
                'url' => route('admin.smart-contracts.recovery', 'retry'),
                'message' => $recoverMessage,
                'enabled' => $canRecover,
            ],
            [
                'title' => 'Recover pending',
                'label' => 'Recover pending transactions',
                'description' => 'Checks unconfirmed transactions against the registry and updates their status.',
                'url' => route('admin.smart-contracts.recovery', 'recover'),
                'message' => $recoverMessage,
                'enabled' => $canRecover,
            ],
        ];

        // ---- Tables ----
        $deploymentHeads = ['Version', 'Network', 'Contract', 'Creation Transaction', 'Deployed'];
    @endphp

    <div class="admin-module space-y-4">
        <x-admin.page-header title="Smart Contract Management" text="Manage permit recording and inspect deployment history.">
            <form method="GET" action="{{ route('admin.smart-contracts.index') }}" data-busy="Refreshing…">
                <input type="hidden" name="refresh" value="1">
                <button class="{{ $button }}">
                    <svg class="mr-2 size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                        <path d="M21 3v5h-5" />
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                        <path d="M8 16H3v5" />
                    </svg>
                    Refresh status
                </button>
            </form>
        </x-admin.page-header>

        @if (session('registry_notice'))
            <p role="status"
                class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                {{ session('registry_notice') }}
            </p>
        @endif

        @if ($problem)
            <p role="alert"
                class="rounded-lg border border-amber-400 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                {{ $problem }}
            </p>
        @endif

        {{-- Blockchain overview --}}
        <div
            class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white lg:grid-cols-4 dark:border-slate-700 dark:bg-slate-900">
            @foreach ($overview as $item)
                <x-admin.stat-card :label="$item['label']" :value="$item['value']" :subtitle="$item['subtitle']" :value-class="$item['valueClass']">
                    <x-slot:icon>
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            {!! $item['icon'] !!}
                        </svg>
                    </x-slot:icon>
                </x-admin.stat-card>
            @endforeach
        </div>

        {{-- Active contract + operational controls --}}
        <div class="grid gap-4 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">

            {{-- Left column: needs attention above the active contract --}}
            <div class="min-w-0 space-y-4">
                {{-- Needs attention: counts per actionable state, each opens the ledger filtered --}}
                <section class="{{ $panel }}">
                    <h2 class="{{ $heading }}">Needs Attention</h2>
                    <p class="{{ $subheading }}">
                        {{ $attentionTotal > 0 ? 'Permit records waiting on action. Select a card to open it in the ledger.' : 'No permit records are waiting on action.' }}
                    </p>

                    <div
                        class="mt-3 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 lg:grid-cols-4 dark:border-slate-700 dark:bg-slate-700">
                        @foreach ($attentionLabels as $state => $label)
                            @php $count = $attention[$state] ?? 0; @endphp

                            <a href="{{ route('admin.blockchain-ledger.index', ['status' => $state]) }}"
                                class="flex items-center justify-between gap-2 bg-white px-4 py-3 text-sm hover:bg-slate-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-(--blue-500) dark:bg-slate-900 dark:hover:bg-slate-800">
                                <span class="text-slate-600 dark:text-slate-300">{{ $label }}</span>
                                <span
                                    class="text-base font-semibold {{ $count > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-slate-100' }}">{{ number_format($count) }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Active contract --}}
                <section class="{{ $panel }}">
                    <h2 class="{{ $heading }}">Active Smart Contract</h2>
                    <p class="{{ $subheading }}">Registry, wallets, and authorization for the contract in use.</p>

                    <div class="mt-3 grid gap-x-4 gap-y-3 md:grid-cols-2">
                        @foreach ($details as $item)
                            @php
                                $isAddress = ($item['type'] ?? null) === 'address';
                                $url = $isAddress ? $link($network, 'address', (string) $item['value']) : null;
                            @endphp

                            {{-- A lone last field spans the full row so no gap is left --}}
                            <div class="min-w-0 md:[&:last-child:nth-child(odd)]:col-span-2">
                                <x-form.input :name="'detail_' . $item['name']" :label="$item['label']" :value="$item['value'] ?? 'Unknown'" :use-old="false"
                                    :raised="true" :light="true" readonly
                                    class="h-12! {{ $isAddress ? 'font-mono sm:text-xs!' : '' }} read-only:cursor-default! read-only:opacity-100!">
                                    @if ($url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener"
                                            class="mt-0.5 inline-block text-xs font-medium text-[#17325c] hover:underline dark:text-slate-300">
                                            View on explorer
                                        </a>
                                    @endif
                                </x-form.input>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- Operational controls --}}
            <section class="{{ $panel }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="{{ $heading }}">Operational Controls</h2>
                        <p class="{{ $subheading }}">Pause recording and recover transactions that did not confirm.</p>
                    </div>

                    @if (auth()->user()->hasPermission('audit.view'))
                        <a class="text-sm font-medium text-[#17325c] hover:underline dark:text-slate-300"
                            href="{{ route('admin.audit-logs.index', ['action' => 'registry_operations']) }}">
                            View audit logs
                        </a>
                    @endif
                </div>


                @if ($isAdmin)
                    <div class="mt-3 grid gap-2.5 md:grid-cols-3 xl:grid-cols-1">
                        @foreach ($actions as $action)
                            <form method="POST" action="{{ $action['url'] }}" data-confirm-title="{{ $action['label'] }}"
                                data-confirm-text="{{ $action['message'] }}"
                                class="flex flex-col justify-between gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                @csrf
                                @if ($action['pause'] ?? false)
                                    <input type="hidden" name="paused" value="{{ $requestPause ? 1 : 0 }}">
                                @endif

                                <div>
                                    <h3 class="text-sm font-semibold">{{ $action['title'] }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $action['description'] }}</p>
                                </div>

                                <button class="{{ $button }} w-full"
                                    @disabled(!$action['enabled'])>{{ $action['label'] }}</button>
                            </form>
                        @endforeach
                    </div>

                    @if (!$problem && empty($info['isOwner']))
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            The contract owner controls pause and resume.
                        </p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Your role has monitoring access.</p>
                @endif
            </section>
        </div>

        {{-- Deployment history --}}
        <x-tables.card title="Contract Deployment History" description="Every version of the permit registry deployed."
            :paginator="$deployments" :searchable="false" item-label="deployments">
            <x-tables.table :heads="$deploymentHeads" :empty="$deployments->isEmpty()" message="No deployments recorded."
                hint="Deployments appear here once the registry is deployed.">
                @foreach ($deployments as $row)
                    <tr>
                        <td data-title>{{ $row->version }}</td>
                        <td data-side="right">
                            <x-tables.badge variant="neutral">{{ ucfirst($row->network) }}</x-tables.badge>
                        </td>

                        @foreach (['address' => ['Contract', $row->contract_address], 'tx' => ['Creation transaction', $row->deploy_tx_hash]] as $kind => [$label, $value])
                            <td data-label="{{ $label }}">
                                @if ($url = $link($row->network, $kind, $value))
                                    <a class="{{ $mono }}" title="{{ $value }}" href="{{ $url }}"
                                        target="_blank" rel="noopener">{{ $short($value) }}</a>
                                @else
                                    <span class="text-slate-400">Unavailable</span>
                                @endif
                            </td>
                        @endforeach

                        <td data-label="Deployed" class="sm:whitespace-nowrap">
                            {{ $row->deployed_at?->format('M j, Y') ?? 'Unknown' }}
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>

    <script>
        // Confirm dangerous actions and show a busy label on submit.
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm-text], form[data-busy]');
            if (!form) return;

            const button = form.querySelector('button');
            const busy = () => {
                button.disabled = true;
                button.textContent = form.dataset.busy || 'Processing…';
            };

            if (!form.dataset.confirmText) return busy();

            event.preventDefault();
            tConfirmation({
                title: form.dataset.confirmTitle,
                text: form.dataset.confirmText,
                confirmLabel: 'Confirm',
            }).then((ok) => {
                if (!ok) return;
                busy();
                form.submit();
            });
        });
    </script>
@endsection