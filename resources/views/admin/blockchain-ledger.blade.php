@extends('layouts.admin')

@section('title', 'Blockchain Transaction Ledger')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">System Integrity</p>
            <h1 class="mt-2 text-2xl font-bold text-white">Blockchain Transaction Ledger</h1>
            <p class="mt-2 text-sm text-blue-100/60">Inspect blockchain transaction references and recorded document hashes.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach (['Total Transactions', 'Confirmed Transactions', 'Failed Transactions'] as $label)
                <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                    <p class="text-sm text-blue-100/60">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold text-white">—</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
            <label for="transaction-search" class="mb-2 block text-sm font-medium text-slate-200">
                Search Transaction
            </label>
            <input id="transaction-search" type="search" placeholder="Enter transaction hash or reference..."
                class="w-full rounded-lg border border-white/10 bg-[#020A18] px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none sm:max-w-lg">
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10 bg-[#09172D]">
            <div class="border-b border-white/10 p-5">
                <h2 class="font-semibold text-white">Transaction History</h2>
                <p class="mt-1 text-sm text-slate-400">Recorded transactions will appear here after blockchain integration.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-187.5 text-left text-sm">
                    <thead class="bg-white/3 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Transaction ID</th>
                            <th class="px-5 py-4">Record Type</th>
                            <th class="px-5 py-4">Document Hash</th>
                            <th class="px-5 py-4">Timestamp</th>
                            <th class="px-5 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                No blockchain transactions connected yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-xs leading-5 text-slate-500">
            Transaction details must come from a trusted blockchain integration or verified ledger source.
            Do not display placeholder values as confirmed transactions.
        </p>
    </div>
@endsection
