@extends('layouts.admin')

@section('title', 'Blockchain Transaction Ledger')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="System Integrity" title="Blockchain Transaction Ledger"
            text="Inspect blockchain transaction references and recorded document hashes." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Total Transactions" />
            <x-admin.stat-card label="Confirmed Transactions" />
            <x-admin.stat-card label="Failed Transactions" />
        </div>

        {{-- Search --}}
        <div class="admin-card p-5">
            <label for="transaction-search" class="mb-2 block text-sm font-medium text-slate-700">Search Transaction</label>
            <input id="transaction-search" type="search" placeholder="Enter transaction hash or reference..."
                class="admin-field sm:max-w-lg">
        </div>

        <x-tables.card title="Transaction History"
            description="Recorded transactions will appear here after blockchain integration."
            :searchable="false">
            <x-tables.table :heads="['Transaction ID', 'Record Type', 'Document Hash', 'Timestamp', 'Status']"
                :empty="true" message="No blockchain transactions connected yet." />
        </x-tables.card>

        <p class="text-xs leading-5 text-slate-500">
            Transaction details must come from a trusted blockchain integration or verified ledger source.
            Do not display placeholder values as confirmed transactions.
        </p>
    </div>
@endsection