@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $recentPermits ??= collect();

        $shortcuts = [
            'admin.users.index' => 'User & Role Management',
            'admin.identity-verification.index' => 'Identity Verification',
            'admin.permits.index' => 'Permit Management',
            'admin.reports.index' => 'Reports & Analytics',
        ];
    @endphp

    <div class="space-y-6">
        <x-admin.page-header
            label="Overview"
            title="Admin Dashboard"
            text="Monitor citizen services and barangay transactions."
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                label="Total Citizens"
                :value="$totalCitizens ?? 0"
                note="Registered citizen accounts"
                :href="route('admin.users.index')"
            />
            <x-admin.stat-card
                label="Pending Permits"
                :value="$pendingPermits ?? 0"
                note="Requests awaiting review"
                :href="route('admin.permits.index')"
            />
            <x-admin.stat-card
                label="Pending Verifications"
                :value="$pendingVerifications ?? 0"
                note="Identity checks to review"
                :href="route('admin.identity-verification.index')"
            />
            <x-admin.stat-card
                label="Verified identities"
                :value="$authenticatedDocuments ?? 0"
                note="Verified identity records"
                :href="route('admin.document-authentication.index')"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Recent Permit Requests --}}
            <section class="min-w-0 xl:col-span-2">
                <x-tables.card title="Recent Permit Requests"
                    description="The latest requests submitted by citizens." :searchable="false">
                    <div class="flex justify-end px-3 pt-3">
                        <a href="{{ route('admin.permits.index') }}"
                            class="text-sm font-semibold text-[#17325c] transition-colors duration-200 hover:text-emerald-700">
                            View all
                        </a>
                    </div>
                    <x-tables.table
                        :heads="['Reference', 'Citizen', 'Permit Type', 'Status']"
                        :empty="$recentPermits->isEmpty()"
                        message="No recent permit requests to display."
                    >
                        @foreach ($recentPermits as $permit)
                            @php $status = strtolower($permit->status ?? 'pending'); @endphp

                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap font-medium text-slate-800">
                                    {{ $permit->reference_number ?? 'REQ-'.$permit->id }}
                                </td>
                                <td class="text-slate-600">
                                    <x-tables.user-cell :name="$permit->user->name ?? ($permit->citizen_name ?? '—')"
                                        :avatar="$permit->user->avatar_url ?? null" />
                                </td>
                                <td class="text-slate-600">
                                    {{ $permit->permit_type ?? ($permit->type ?? '—') }}
                                </td>
                                <td class="whitespace-nowrap">
                                    <x-tables.badge :variant="match ($status) {
                                        'approved', 'completed', 'authenticated' => 'success',
                                        'rejected', 'denied' => 'danger',
                                        'pending', 'processing', 'under_review' => 'warning',
                                        default => 'neutral',
                                    }" class="capitalize">
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </x-tables.badge>
                                </td>
                            </tr>
                        @endforeach
                    </x-tables.table>
                </x-tables.card>
            </section>

            {{-- Admin Shortcuts --}}
            <section class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Admin Shortcuts</h2>
                </div>

                <div class="space-y-2 p-5">
                    @foreach ($shortcuts as $routeName => $label)
                        <a href="{{ route($routeName) }}"
                           class="group flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-50 hover:shadow-sm">
                            <span>{{ $label }}</span>
                            <span class="text-[#17325c] transition-transform duration-200 group-hover:translate-x-1 group-hover:text-emerald-700">&rarr;</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection