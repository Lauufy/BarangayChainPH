@extends('layouts.admin')

@use('Illuminate\Support\Str')

@section('title', 'Blockchain Transaction Ledger')

@section('content')
    <div class="admin-module space-y-5">
        <x-admin.page-header title="Blockchain Transaction Ledger"
            text="Inspect blockchain transaction references and recorded document hashes." />

        {{-- Summary --}}
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-4">
            <x-admin.stat-card label="Confirmed Transactions" subtitle="Recordings and revocations"
                :value="number_format($confirmed ?? 0)" :latest="$confirmedLatest ?? null" latest-label="Last confirmed"
                empty-text="No confirmed transactions yet">
                <x-slot:icon>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Revoked Transactions" subtitle="Permits with a recorded revocation"
                :value="number_format($revoked ?? 0)" :latest="$revokedLatest ?? null" latest-label="Last revoked"
                empty-text="No revoked permits">
                <x-slot:icon>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="m4.9 4.9 14.2 14.2" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending / Paused" subtitle="Waiting to be written to the ledger"
                :value="number_format($waiting ?? 0)" :latest="$waitingLatest ?? null" latest-label="Last update"
                empty-text="Nothing waiting">
                <x-slot:icon>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="10" x2="10" y1="15" y2="9" />
                        <line x1="14" x2="14" y1="15" y2="9" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Failed Transactions" subtitle="Unsuccessful ledger writes"
                :value="number_format($failed ?? 0)" :latest="$failedLatest ?? null" latest-label="Last failure"
                empty-text="No failed transactions" :invert="true">
                <x-slot:icon>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Transaction directory --}}
        <x-tables.card :paginator="$records" :searchable="false" :per-page-options="[5, 10, 15, 25]" item-label="records">
            <x-tables.toolbar key="blockchain-ledger" :total="$total ?? 0" total-label="All Transactions" :search="true"
                search-placeholder="Search by transaction or permit number"
                :filters="[
                    [
                        'name' => 'status',
                        'title' => 'Transaction status',
                        'allLabel' => 'All statuses',
                        'options' => $statuses ?? ['confirmed' => 'Confirmed', 'failed' => 'Failed', 'revoked' => 'Revoked'],
                        'selected' => $status ?? null,
                    ],
                ]" />

            <x-tables.table
                :heads="['Permit', 'Issue Transaction', 'Revoke Transaction', 'Document Hash', 'Status', 'Recorded']"
                :empty="$records->isEmpty()" message="No blockchain records yet."
                hint="Records appear here once a permit is written to the blockchain.">
                @foreach ($records as $record)
                    <tr>
                        {{-- Card title on mobile --}}
                        <td data-title>
                            {{ $record->issuedDocument->document_number }}
                            <span class="text-xs font-normal text-slate-500">v{{ $record->issuedDocument->version }}</span>
                        </td>

                        <td data-label="Issue transaction">
                            @if ($url = $record->transactionUrl($record->tx_hash))
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                    class="font-mono text-xs text-[#17325c] hover:underline dark:text-slate-300">
                                    {{ Str::limit($record->tx_hash, 16) }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        {{-- Tap-to-expand on mobile --}}
                        <td data-label="Revoke transaction" data-detail>
                            @if ($url = $record->transactionUrl($record->revoke_tx_hash))
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                    class="font-mono text-xs text-[#17325c] hover:underline dark:text-slate-300">
                                    {{ Str::limit($record->revoke_tx_hash, 16) }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td data-label="Document hash" data-detail class="font-mono text-xs">
                            {{ Str::limit($record->issuedDocument->file_hash, 16) }}
                        </td>

                        {{-- Top-right of the card on mobile --}}
                        <td data-side="right">
                            @if ($record->issuanceState() === 'confirmed' && ! $record->tx_hash)
                                <x-tables.badge variant="warning" class="gap-1.5 rounded-full!">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    Hash recovery needed
                                </x-tables.badge>
                            @else
                                <x-tables.badge
                                    :variant="match ($record->issuanceState()) {
                                        'confirmed' => 'success',
                                        'failed' => 'danger',
                                        'revoked' => 'neutral',
                                        default => 'warning',
                                    }"
                                    class="gap-1.5 rounded-full!">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ ucfirst($record->issuanceState()) }}
                                </x-tables.badge>
                            @endif
                        </td>

                        <td data-label="Recorded" class="sm:whitespace-nowrap">
                            {{ $record->recorded_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'N/A' }}
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

        <p class="text-xs leading-5 text-slate-500">
            Transaction details come from the records saved when each permit was written to the blockchain.
            Open a transaction link to check it on the public explorer.
        </p>
    </div>
@endsection