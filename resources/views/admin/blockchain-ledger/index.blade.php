@extends('layouts.admin')

@use('Illuminate\Support\Str')

@section('title', 'Blockchain Transaction Ledger')

@section('content')
    <div class="admin-module space-y-5">
        {{-- Explain the ledger and show its key transaction totals. --}}
        <x-admin.page-header title="Blockchain Transaction Ledger"
            text="Inspect blockchain transaction references and recorded document hashes." />

        {{-- Summary of confirmed, failed, and total records. --}}
        <div class="grid gap-3 md:grid-cols-3">
            <x-admin.stat-card label="Total Transactions" subtitle="Confirmed and failed" :value="number_format($total ?? 0)" :latest="$totalLatest ?? null"
                latest-label="Last activity">
                <x-slot:icon>
                    {{-- Lucide: link --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Confirmed Transactions" subtitle="Issued or revoked on-chain" :value="number_format($confirmed ?? 0)"
                :latest="$confirmedLatest ?? null" latest-label="Last confirmed">
                <x-slot:icon>
                    {{-- Lucide: shield-check --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Failed Transactions" subtitle="Need a retry" :value="number_format($failed ?? 0)" :invert="true"
                :latest="$failedLatest ?? null" latest-label="Last failure">
                <x-slot:icon>
                    {{-- Lucide: circle-alert --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Search and paginate blockchain records with transaction references. --}}
        <x-tables.card title="Transaction History"
            description="Every permit recorded on the blockchain, with its transaction references." :paginator="$records"
            :searchable="false" item-label="records">
            <x-slot:toolbar>
                <x-tables.toolbar key="blockchain-ledger" :search="true"
                    search-placeholder="Search by transaction or permit number" class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Permit', 'Issue Transaction', 'Revoke Transaction', 'Document Hash', 'Recorded', 'Status']" :empty="$records->isEmpty()" message="No blockchain records yet.">
                @foreach ($records as $record)
                    <tr>
                        <td class="font-medium text-slate-700 dark:text-slate-300">
                            {{ $record->issuedDocument->document_number }}
                            <span
                                class="text-xs font-normal text-slate-700 dark:text-slate-300">v{{ $record->issuedDocument->version }}</span>
                        </td>

                        <td class="text-slate-700 dark:text-slate-300">
                            @if ($url = $record->transactionUrl($record->tx_hash))
                                <a href="{{ $url }}" target="_blank"
                                    rel="noopener" class="font-mono text-xs text-[#17325c] dark:text-slate-300 hover:underline">
                                    {{ Str::limit($record->tx_hash, 16) }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td class="text-slate-700 dark:text-slate-300">
                            @if ($url = $record->transactionUrl($record->revoke_tx_hash))
                                <a href="{{ $url }}"
                                    target="_blank" rel="noopener" class="font-mono text-xs text-[#17325c] dark:text-slate-300 hover:underline">
                                    {{ Str::limit($record->revoke_tx_hash, 16) }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td class="font-mono text-xs text-slate-700 dark:text-slate-300">
                            {{ Str::limit($record->issuedDocument->file_hash, 16) }}
                        </td>

                        <td>
                            @php($state = $record->issuanceState())
                            @if ($state === 'confirmed' && !$record->tx_hash)
                                <x-tables.badge variant="warning">Hash recovery needed</x-tables.badge>
                            @else
                                <x-tables.badge :variant="match ($state) { 'confirmed' => 'success', 'failed' => 'danger', 'revoked' => 'neutral', default => 'warning' }">{{ ucfirst($state) }}</x-tables.badge>
                            @endif
                        </td>

                        <td class="whitespace-nowrap text-slate-700 dark:text-slate-300">
                            {{ $record->recorded_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
                        </td>

                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

        {{-- Explain the source of the ledger entries. --}}
        <p class="text-xs leading-5 text-slate-500">
            Transaction details come from the records saved when each permit was written to the blockchain.
            Open a transaction link to check it on the public explorer.
        </p>
    </div>
@endsection
