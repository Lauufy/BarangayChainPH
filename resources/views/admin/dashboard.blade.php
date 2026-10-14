@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $recentPermits = $recentPermits ?? collect();
    @endphp

    <div class="space-y-6">
        {{-- Page Header --}}
        <x-admin.page-header
            label="Overview"
            title="Admin Dashboard"
            text="Monitor citizen services and barangay transactions."
        />

        {{-- Dashboard Statistics --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                label="Total Citizens"
                :value="$totalCitizens ?? 0"
                note="Registered citizen accounts"
            />

            <x-admin.stat-card
                label="Pending Permits"
                :value="$pendingPermits ?? 0"
                note="Requests awaiting review"
            />

            <x-admin.stat-card
                label="Pending Verifications"
                :value="$pendingVerifications ?? 0"
                note="Identity checks to review"
            />

            <x-admin.stat-card
                label="Documents Authenticated"
                :value="$authenticatedDocuments ?? 0"
                note="Verified documents"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">

            {{-- Recent Permit Requests --}}
            <section class="admin-card min-w-0 xl:col-span-2">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">
                        Recent Permit Requests
                    </h2>

                    <a
                        href="{{ url('/admin/permits') }}"
                        class="text-sm font-semibold text-[#17325c] transition-colors duration-200 hover:text-emerald-700"
                    >
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <x-admin.table
                        :heads="['Reference', 'Citizen', 'Permit Type', 'Status']"
                        :empty="$recentPermits->isEmpty()"
                        message="No recent permit requests to display."
                    >
                        @forelse ($recentPermits as $permit)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-800">
                                    {{ $permit->reference_number ?? 'REQ-' . $permit->id }}
                                </td>

                                <td class="px-5 py-3 text-slate-600">
                                    {{ $permit->user->name ?? ($permit->citizen_name ?? '—') }}
                                </td>

                                <td class="px-5 py-3 text-slate-600">
                                    {{ $permit->permit_type ?? ($permit->type ?? '—') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-3">
                                    @php
                                        $status = strtolower($permit->status ?? 'pending');

                                        $statusVariant = match ($status) {
                                            'approved', 'completed', 'authenticated' => 'success',
                                            'rejected', 'denied' => 'danger',
                                            'pending', 'processing', 'under_review' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp

                                    <x-badge :variant="$statusVariant">
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            {{-- Empty state is handled by the table component --}}
                        @endforelse
                    </x-admin.table>
                </div>
            </section>

            {{-- Admin Shortcuts --}}
            <section class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">
                        Admin Shortcuts
                    </h2>
                </div>

                <div class="space-y-2 p-5">
                    @foreach ([
                        ['/admin/users', 'User & Role Management'],
                        ['/admin/identity-verification', 'Identity Verification'],
                        ['/admin/permits', 'Permit Management'],
                        ['/admin/reports', 'Reports & Analytics'],
                    ] as [$url, $label])
                        <a
                            href="{{ url($url) }}"
                            class="group flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 hover:shadow-sm"
                        >
                            <span>{{ $label }}</span>

                            <span class="text-[#17325c] transition-transform duration-200 group-hover:translate-x-1 group-hover:text-emerald-700">
                                &rarr;
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

        </div>
    </div>
@endsection
