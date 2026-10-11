@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <p class="text-sm font-medium text-emerald-600">OVERVIEW</p>
    <h1 class="mt-1 text-2xl font-bold text-slate-900">Admin Dashboard</h1>
    <p class="mt-1 text-sm text-slate-500">Monitor citizen services and barangay transactions.</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Total Citizens', $totalCitizens ?? 0, 'Registered citizen accounts'],
        ['Pending Permits', $pendingPermits ?? 0, 'Requests awaiting review'],
        ['Pending Verifications', $pendingVerifications ?? 0, 'Identity checks to review'],
        ['Documents Authenticated', $authenticatedDocuments ?? 0, 'Verified documents']
    ] as [$label, $value, $description])
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format((int) $value) }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ $description }}</p>
        </section>
    @endforeach
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-5 xl:col-span-2">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-slate-900">Recent Permit Requests</h2>
            <a href="{{ url('/admin/permits') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">View all</a>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-150 text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-3 py-3">Reference</th><th class="px-3 py-3">Citizen</th><th class="px-3 py-3">Permit Type</th><th class="px-3 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse (($recentPermits ?? collect()) as $permit)
                    <tr>
                        <td class="px-3 py-3 font-medium text-slate-800">{{ $permit->reference_number ?? ('REQ-' . $permit->id) }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $permit->user->name ?? $permit->citizen_name ?? '—' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $permit->permit_type ?? $permit->type ?? '—' }}</td>
                        <td class="px-3 py-3"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">{{ ucfirst($permit->status ?? 'Pending') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No recent permit requests to display.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="font-semibold text-slate-900">Admin Shortcuts</h2>
        <div class="mt-4 space-y-2">
            @foreach ([['User & Role Management', '/admin/users'], ['Identity Verification', '/admin/identity-verification'], ['Permit Management', '/admin/permits'], ['Reports & Analytics', '/admin/reports']] as [$label, $url])
                <a href="{{ url($url) }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:bg-emerald-50">
                    {{ $label }} <span class="text-emerald-700">→</span>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
