@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="System Insights" title="Reports & Analytics"
            text="Review barangay service activity and system performance." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card label="Registered Citizens" note="Awaiting database integration" />
            <x-admin.stat-card label="Permit Applications" note="Awaiting database integration" />
            <x-admin.stat-card label="Verified Documents" note="Awaiting database integration" />
            <x-admin.stat-card label="Completed Requests" note="Awaiting database integration" />
        </div>

        {{-- Chart placeholders --}}
        <div class="grid gap-6 xl:grid-cols-2">
            <div class="admin-card p-5">
                <h2 class="font-semibold text-slate-900">Permit Request Summary</h2>
                <p class="mt-1 text-sm text-slate-500">Application totals by status.</p>
                <div class="mt-5 flex min-h-40 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50">
                    <p class="text-sm text-slate-400">Chart will appear when report data is connected.</p>
                </div>
            </div>

            <div class="admin-card p-5">
                <h2 class="font-semibold text-slate-900">Monthly Service Activity</h2>
                <p class="mt-1 text-sm text-slate-500">Track service usage over time.</p>
                <div class="mt-5 flex min-h-40 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50">
                    <p class="text-sm text-slate-400">Chart will appear when report data is connected.</p>
                </div>
            </div>
        </div>

        {{-- Generate report --}}
        <div class="admin-card p-5">
            <h2 class="font-semibold text-slate-900">Generate Report</h2>
            <p class="mt-1 text-sm text-slate-500">Report generation and export can be added after the reporting queries are implemented.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="report-type" class="mb-1 block text-sm font-medium text-slate-700">Report Type</label>
                    <select id="report-type" class="admin-field">
                        <option value="permits">Permit Applications</option>
                        <option value="users">User Accounts</option>
                        <option value="documents">Document Authentication</option>
                        <option value="activity">System Activity</option>
                    </select>
                </div>

                <div>
                    <label for="report-period" class="mb-1 block text-sm font-medium text-slate-700">Reporting Period</label>
                    <select id="report-period" class="admin-field">
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