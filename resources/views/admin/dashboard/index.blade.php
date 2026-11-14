@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $can = fn($permission) => $user->hasPermission($permission);

        $workload = $workload ?? null;
        $recentPermits = $recentPermits ?? collect();

        // Only show shortcuts this person can open: [route, label, allowed]
        $shortcuts = collect([
            ['admin.users.index', 'User & Role Management', $isAdmin],
            ['admin.identity-verification.index', 'Identity Verification', $can('identity.review')],
            ['admin.permits.index', 'Permit Management', $can('permits.review')],
            ['admin.reports.index', 'Reports & Analytics', $can('reports.view')],
        ])->filter(fn($shortcut) => $shortcut[2]);

        // Shared classes, so every panel on the page looks the same
        $panel = 'rounded-2xl border border-(--gray-200) bg-(--white) shadow-sm dark:border-white/10 dark:bg-(--gray-800)';
        $panelHead = 'border-b border-(--gray-200) px-5 py-4 dark:border-white/10';
        $panelTitle = 'text-base font-semibold text-(--gray-900) dark:text-(--gray-50)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';

        $miniCard = 'rounded-xl bg-(--gray-50) p-4 dark:bg-(--gray-900)';
        $miniTitle = 'text-sm text-(--gray-600) dark:text-(--gray-300)';
        $miniNumber = 'mt-1 text-2xl font-bold tabular-nums text-(--gray-900) dark:text-(--gray-50)';
    @endphp

    <div class="space-y-6">

        {{-- Page heading --}}
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-(--green-600) dark:text-(--green-400)">Overview</p>
            <h1 class="mt-1 text-2xl font-bold text-(--gray-900) dark:text-(--white)">Admin Dashboard</h1>
            <p class="{{ $muted }} mt-1 text-sm">Monitor citizen services and barangay transactions.</p>
        </div>

        {{-- Warning: approved permits not yet on the blockchain --}}
        @if (($unrecordedPermits ?? 0) > 0 && $can('permits.review'))
            <div
                class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                {{ $unrecordedPermits }} approved permit(s) not confirmed on the blockchain yet.
                Open the permit and press "Record on blockchain".
                <a href="{{ route('admin.permits.index') }}" class="font-semibold underline">Go to Permit Management</a>
            </div>
        @endif

        {{-- Stat cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card label="Total Citizens" subtitle="Registered accounts"
                :value="number_format($totalCitizens ?? 0)" :trend="$citizensTrend ?? null"
                :latest="$citizensLatest ?? null" latest-label="Last registered"
                :href="$isAdmin ? route('admin.users.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 19.13a9.37 9.37 0 0 0 4.12-.96 4.13 4.13 0 0 0-7.53-2.49M15 19.13V19.13a8.97 8.97 0 0 1-4.5 1.2 9 9 0 0 1-4.5-1.2m9 0a6.38 6.38 0 0 0-9 0m9 0v-.1a3.37 3.37 0 0 0-9 0v.1M12.75 7.5a3.38 3.38 0 1 1-6.75 0 3.38 3.38 0 0 1 6.75 0Zm6 2.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Permits" subtitle="Awaiting review"
                :value="number_format($pendingPermits ?? 0)" :trend="$permitsTrend ?? null" :invert="true"
                :latest="$permitsLatest ?? null" latest-label="Last submitted"
                :href="$can('permits.review') ? route('admin.permits.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.63a3.38 3.38 0 0 0-3.38-3.37h-1.5a1.13 1.13 0 0 1-1.12-1.13v-1.5a3.38 3.38 0 0 0-3.38-3.37H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.63c-.62 0-1.13.5-1.13 1.12v17.25c0 .62.5 1.13 1.13 1.13h12.75c.62 0 1.12-.5 1.12-1.13V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Verifications" subtitle="Identity checks"
                :value="number_format($pendingVerifications ?? 0)" :trend="$verificationsTrend ?? null" :invert="true"
                :latest="$verificationsLatest ?? null" latest-label="Last submitted"
                :href="$can('identity.review') ? route('admin.identity-verification.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            {{-- Text matches DashboardLabelTest --}}
            <x-admin.stat-card label="Verified identities" subtitle="Verified identity records"
                :value="number_format($authenticatedDocuments ?? 0)" :trend="$verifiedTrend ?? null"
                :latest="$verifiedLatest ?? null" latest-label="Last verified"
                :href="$can('identity.review') ? route('admin.identity-verification.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75m-3-7.04A11.96 11.96 0 0 1 3.6 6 12 12 0 0 0 3 9.75c0 5.59 3.82 10.29 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.31-.21-2.57-.6-3.75h-.15c-3.2 0-6.1-1.25-8.25-3.29Z" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- My workload --}}
        @if ($workload)
            <section class="{{ $panel }} p-5" aria-labelledby="staff-workload-heading">
                <h2 id="staff-workload-heading" class="{{ $panelTitle }}">My workload</h2>
                <p class="{{ $muted }} mt-1 text-sm">
                    @if ($workload['can_review_permits'] || $workload['can_review_identity'])
                        Open items in the queues you can review.
                    @else
                        No review queues are assigned to your account.
                    @endif
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @if ($workload['can_review_permits'])
                        <div class="{{ $miniCard }}">
                            <p class="{{ $miniTitle }}">Requests awaiting action</p>
                            <p class="{{ $miniNumber }}">{{ $workload['requests'] }}</p>
                        </div>
                    @endif

                    @if ($workload['can_review_identity'])
                        <div class="{{ $miniCard }}">
                            <p class="{{ $miniTitle }}">Identity reviews in queue</p>
                            <p class="{{ $miniNumber }}">{{ $workload['identity_reviews'] }}</p>
                        </div>
                    @endif

                    <div class="{{ $miniCard }}">
                        <p class="{{ $miniTitle }}">Oldest pending item</p>
                        @if ($workload['oldest_item'])
                            <p class="mt-1 font-semibold text-(--gray-900) dark:text-(--gray-50)">
                                {{ $workload['oldest_item']['label'] }}</p>
                            <p class="{{ $muted }} mt-1 text-xs">{{ $workload['oldest_item']['age_days'] }} days old</p>
                        @else
                            <p class="{{ $muted }} mt-1 text-sm">No open items.</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">

            {{-- Recent permit requests --}}
            <section class="{{ $panel }} min-w-0 overflow-hidden xl:col-span-2">
                <div class="{{ $panelHead }} flex items-start justify-between gap-3">
                    <div>
                        <h2 class="{{ $panelTitle }}">Recent Permit Requests</h2>
                        <p class="{{ $muted }} mt-0.5 text-sm">The latest requests submitted by citizens.</p>
                    </div>

                    @if ($can('permits.review'))
                        <a href="{{ route('admin.permits.index') }}"
                            class="shrink-0 text-sm font-semibold text-(--blue-700) transition-colors hover:text-(--green-700) dark:text-(--blue-300) dark:hover:text-(--green-400)">
                            View all
                        </a>
                    @endif
                </div>

                @if ($recentPermits->isEmpty())
                    <p class="{{ $muted }} px-5 py-12 text-center text-sm">No recent permit requests to display.</p>
                @else
                    <x-tables.table :heads="['Reference', 'Citizen', 'Permit Type', 'Status']" :empty="false">
                        @foreach ($recentPermits as $permit)
                            @php $status = strtolower($permit->status ?? 'pending'); @endphp

                            <tr class="transition-colors duration-150 hover:bg-(--gray-50) dark:hover:bg-white/5">
                                <td class="whitespace-nowrap font-medium text-(--gray-800) dark:text-(--gray-100)">
                                    {{ $permit->reference_number ?? 'REQ-' . $permit->id }}
                                </td>
                                <td class="text-(--gray-600) dark:text-(--gray-300)">
                                    <x-tables.user-cell :name="$permit->user->name ?? ($permit->citizen_name ?? '—')"
                                        :avatar="$permit->user->avatar_url ?? null" />
                                </td>
                                <td class="text-(--gray-600) dark:text-(--gray-300)">
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
                @endif
            </section>

            {{-- Shortcuts --}}
            <section class="{{ $panel }} self-start">
                <div class="{{ $panelHead }}">
                    <h2 class="{{ $panelTitle }}">Shortcuts</h2>
                </div>

                <div class="space-y-2 p-5">
                    @forelse ($shortcuts as [$routeName, $label])
                        <a href="{{ route($routeName) }}"
                            class="group flex items-center justify-between rounded-xl border border-(--gray-200) px-3 py-3 text-sm font-medium text-(--gray-700) transition-all duration-200 hover:-translate-y-0.5 hover:border-(--green-300) hover:bg-(--green-50) hover:shadow-sm dark:border-white/10 dark:text-(--gray-200) dark:hover:border-(--green-500) dark:hover:bg-(--green-500)/10">
                            <span>{{ $label }}</span>
                            <span
                                class="text-(--blue-700) transition-transform duration-200 group-hover:translate-x-1 group-hover:text-(--green-700) dark:text-(--blue-300) dark:group-hover:text-(--green-400)">&rarr;</span>
                        </a>
                    @empty
                        <p class="{{ $muted }} py-4 text-center text-sm">No shortcuts available.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection