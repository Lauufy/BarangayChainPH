@extends('layouts.admin')

@use('Illuminate\Support\Str')

@section('title', 'Blockchain Transaction Ledger')

@section('content')
    <div class="space-y-6">
        {{-- Explain the ledger and show its key transaction totals. --}}
        <x-admin.page-header label="System Integrity" title="Blockchain Transaction Ledger"
            text="Inspect blockchain transaction references and recorded document hashes."
            :export-url="route('admin.exports.show', ['module' => 'blockchain-ledger'])" />

        {{-- Summary of confirmed, failed, and total records. --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Total Transactions" :value="$total" />
            <x-admin.stat-card label="Confirmed Transactions" :value="$confirmed" />
            <x-admin.stat-card label="Failed Transactions" :value="$failed" />
        </div>

        {{-- Search and paginate blockchain records with transaction references. --}}
        <x-tables.card title="Transaction History"
            description="Every permit recorded on the blockchain, with its transaction references."
            :paginator="$records" :searchable="false" item-label="records">
            <x-slot:toolbar>
                <x-tables.toolbar key="blockchain-ledger" :search="true"
                    search-placeholder="Search by transaction or permit number"
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table
                :heads="['Permit', 'Issue Transaction', 'Revoke Transaction', 'Document Hash', 'Recorded', 'Status']"
                :empty="$records->isEmpty()" message="No blockchain records yet.">
                @foreach ($records as $record)
                    <tr>
                        <td class="font-medium text-slate-900">
                            {{ $record->issuedDocument->document_number }}
                            <span class="text-xs font-normal text-slate-500">v{{ $record->issuedDocument->version }}</span>
                        </td>

                        <td>
                            @if ($record->tx_hash)
                                <a href="{{ config('blockchain.explorer_url') }}/tx/{{ $record->tx_hash }}" target="_blank"
                                    rel="noopener" class="font-mono text-xs text-[#17325c] hover:underline">
                                    {{ Str::limit($record->tx_hash, 16) }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td>
                            @if ($record->revoke_tx_hash)
                                <a href="{{ config('blockchain.explorer_url') }}/tx/{{ $record->revoke_tx_hash }}"
                                    target="_blank" rel="noopener" class="font-mono text-xs text-[#17325c] hover:underline">
                                    {{ Str::limit($record->revoke_tx_hash, 16) }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        <td class="font-mono text-xs">{{ Str::limit($record->issuedDocument->file_hash, 16) }}</td>

                        <td class="whitespace-nowrap">
                            {{ $record->recorded_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
                        </td>

                        <td>
                            @if ($record->recorded_at && ! $record->tx_hash)
                                <x-tables.badge variant="warning">Hash recovery needed</x-tables.badge>
                            @elseif ($record->revoked_at)
                                <x-tables.badge variant="neutral">Revoked</x-tables.badge>
                            @elseif ($record->recorded_at)
                                <x-tables.badge variant="success">Confirmed</x-tables.badge>
                            @elseif ($record->error)
                                <x-tables.badge variant="danger">Failed</x-tables.badge>
                            @else
                                <x-tables.badge variant="warning">Pending</x-tables.badge>
                            @endif
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