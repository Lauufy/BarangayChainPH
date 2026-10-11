@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">System Insights</p>
            <h1 class="mt-2 text-2xl font-bold text-white">Reports & Analytics</h1>
            <p class="mt-2 text-sm text-blue-100/60">Review barangay service activity and system performance.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach (['Registered Citizens', 'Permit Applications', 'Verified Documents', 'Completed Requests'] as $label)
                <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                    <p class="text-sm text-blue-100/60">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold text-white">—</p>
                    <p class="mt-2 text-xs text-slate-500">Awaiting database integration</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                <h2 class="font-semibold text-white">Permit Request Summary</h2>
                <p class="mt-2 text-sm text-slate-400">Application totals by status.</p>
                <div class="mt-6 flex min-h-40 items-center justify-center rounded-lg border border-dashed border-white/10">
                    <p class="text-sm text-slate-500">Chart will appear when report data is connected.</p>
                </div>
            </div>

            <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
                <h2 class="font-semibold text-white">Monthly Service Activity</h2>
                <p class="mt-2 text-sm text-slate-400">Track service usage over time.</p>
                <div class="mt-6 flex min-h-40 items-center justify-center rounded-lg border border-dashed border-white/10">
                    <p class="text-sm text-slate-500">Chart will appear when report data is connected.</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-white/10 bg-[#09172D] p-5">
            <h2 class="font-semibold text-white">Generate Report</h2>
            <p class="mt-2 text-sm text-slate-400">
                Report generation and export can be added after the reporting queries are implemented.
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="report-type" class="mb-2 block text-sm text-slate-300">Report Type</label>
                    <select id="report-type"
                        class="w-full rounded-lg border border-white/10 bg-[#020A18] px-3 py-3 text-sm text-white">
                        <option value="permits">Permit Applications</option>
                        <option value="users">User Accounts</option>
                        <option value="documents">Document Authentication</option>
                        <option value="activity">System Activity</option>
                    </select>
                </div>

                <div>
                    <label for="report-period" class="mb-2 block text-sm text-slate-300">Reporting Period</label>
                    <select id="report-period"
                        class="w-full rounded-lg border border-white/10 bg-[#020A18] px-3 py-3 text-sm text-white">
                        <option value="month">This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
@endsection
