@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $can = fn ($permission) => $user->hasPermission($permission);

        // Only show shortcuts that this person can actually open: [route, label, allowed]
        $shortcuts = collect([
            ['admin.users.index', 'User & Role Management', $isAdmin],
            ['admin.identity-verification.index', 'Identity Verification', $can('identity.review')],
            ['admin.permits.index', 'Permit Management', $can('permits.review')],
            ['admin.reports.index', 'Reports & Analytics', $isAdmin && $can('reports.view')],
        ])->filter(fn ($shortcut) => $shortcut[2]);
    @endphp

    <div class="space-y-6">
        <x-admin.page-header
            label="Overview"
            title="Admin Dashboard"
            text="Monitor citizen services and barangay transactions."
        />

        @if (($unrecordedPermits ?? 0) > 0 && $can('permits.review'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                {{ $unrecordedPermits }} approved permit(s) not confirmed on the blockchain yet.
                Open the permit and press "Record on blockchain".
                <a href="{{ route('admin.permits.index') }}" class="font-semibold underline">Go to Permit Management</a>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                label="Total Citizens"
                :value="$totalCitizens ?? 0"
                note="Registered citizen accounts"
                :href="$isAdmin ? route('admin.users.index') : null"
            />
            <x-admin.stat-card
                label="Pending Permits"
                :value="$pendingPermits ?? 0"
                note="Requests awaiting review"
                :href="$can('permits.review') ? route('admin.permits.index') : null"
            />
            <x-admin.stat-card
                label="Pending Verifications"
                :value="$pendingVerifications ?? 0"
                note="Identity checks to review"
                :href="$can('identity.review') ? route('admin.identity-verification.index') : null"
            />
            <x-admin.stat-card
                label="Verified identities"
                :value="$authenticatedDocuments ?? 0"
                note="Verified identity records"
                :href="$can('identity.review') ? route('admin.identity-verification.index') : null"
            />
        </div>

        @if ($workload)
            <section class="admin-card p-5" aria-labelledby="staff-workload-heading">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="staff-workload-heading" class="font-semibold text-slate-900">My workload</h2>
                        <p class="mt-1 text-sm text-slate-500">Open items in the queues you can review.</p>
                    </div>
                    @if (! $workload['can_review_permits'] && ! $workload['can_review_identity'])
                        <p class="text-sm text-slate-500">No review queues are assigned to your account.</p>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @if ($workload['can_review_permits'])
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-sm text-slate-600">Requests awaiting action</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $workload['requests'] }}</p>
                        </div>
                    @endif
                    @if ($workload['can_review_identity'])
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-sm text-slate-600">Identity reviews in queue</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $workload['identity_reviews'] }}</p>
                        </div>
                    @endif
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm text-slate-600">Oldest pending item</p>
                        @if ($workload['oldest_item'])
                            <p class="mt-1 font-semibold text-slate-900">{{ $workload['oldest_item']['label'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $workload['oldest_item']['age_days'] }} days old</p>
                        @else
                            <p class="mt-1 text-sm text-slate-500">No open items.</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Recent Permit Requests --}}
            <section class="min-w-0 xl:col-span-2">
                <x-tables.card title="Recent Permit Requests"
                    description="The latest requests submitted by citizens." :searchable="false">
                    @if ($can('permits.review'))
                        <div class="flex justify-end px-3 pt-3">
                            <a href="{{ route('admin.permits.index') }}"
                                class="text-sm font-semibold text-[#17325c] transition-colors duration-200 hover:text-emerald-700">
                                View all
                            </a>
                        </div>
                    @endif
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
                                    {{ $permit->serviceTitle() }}
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

            {{-- Shortcuts (only the ones this person can open) --}}
            <section class="admin-card">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="font-semibold text-slate-900">Shortcuts</h2>
                </div>

                <div class="space-y-2 p-5">
                    @foreach ($shortcuts as [$routeName, $label])
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