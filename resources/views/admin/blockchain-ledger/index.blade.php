@extends('layouts.admin')

@use('Illuminate\Support\Str')

@section('title', 'Blockchain Transaction Ledger')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="System Integrity" title="Blockchain Transaction Ledger"
            text="Inspect blockchain transaction references and recorded document hashes." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Total Transactions" :value="$total" />
            <x-admin.stat-card label="Confirmed Transactions" :value="$confirmed" />
            <x-admin.stat-card label="Failed Transactions" :value="$failed" />
        </div>

        <x-tables.card title="Transaction History"
            description="Every permit recorded on the blockchain, with its transaction references."
            :paginator="$records" item-label="records"
            search-placeholder="Search by transaction or permit number">
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
                                <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Hash recovery needed</span>
                            @elseif ($record->revoked_at)
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-700">Revoked</span>
                            @elseif ($record->recorded_at)
                                <span class="rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-800">Confirmed</span>
                            @elseif ($record->error)
                                <span class="rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-800">Failed</span>
                            @else
                                <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Pending</span>
                            @endif
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