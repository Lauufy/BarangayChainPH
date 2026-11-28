@extends('layouts.admin')

@section('title', 'Dashboard')

@push('page-scripts')
    @vite('resources/js/reports.js')
@endpush

@section('content')
    <div class="admin-module space-y-5" data-reports-page>

        {{-- Welcome the signed-in user and describe the dashboard. --}}
        <x-admin.page-header :title="$greeting . ', ' . $firstName . '.'" text="Monitor citizen services and barangay transactions." />

        {{-- Warning: approved permits not yet on the blockchain --}}
        @if (($unrecordedPermits ?? 0) > 0 && $can('permits.review'))
            <div
                class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                {{ $unrecordedPermits }} approved permit(s) not confirmed on the blockchain yet.
                Open the permit and press "Record on blockchain".
                <a href="{{ route('admin.permits.index') }}" class="font-semibold underline">Go to Permit Management</a>
            </div>
        @endif

        <div class="grid grid-cols-1 min-[420px]:grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-4">
            <x-admin.stat-card label="Total Citizens" subtitle="Registered accounts" :value="number_format($totalCitizens ?? 0)" :trend="$citizensTrend ?? null"
                :latest="$citizensLatest ?? null" latest-label="Last registered" :href="$isAdmin ? route('admin.users.index') : null">
                <x-slot:icon>
                    {{-- Lucide: users --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <path d="M16 3.128a4 4 0 0 1 0 7.744" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <circle cx="9" cy="7" r="4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Permits" subtitle="Awaiting review or processing" :value="number_format($pendingPermits ?? 0)" :trend="$permitsTrend ?? null"
                :invert="true" :latest="$permitsLatest ?? null" latest-label="Last submitted" :href="$can('permits.review') ? route('admin.permits.index') : null">
                <x-slot:icon>
                    {{-- Lucide: clipboard-check --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                        <path d="m9 14 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Verifications" subtitle="Identity checks" :value="number_format($pendingVerifications ?? 0)"
                :trend="$verificationsTrend ?? null" :invert="true" :latest="$verificationsLatest ?? null" latest-label="Last submitted" :href="$can('identity.review') ? route('admin.identity-verification.index') : null">
                <x-slot:icon>
                    {{-- Lucide: id-card --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 10h2" />
                        <path d="M16 14h2" />
                        <path d="M6.17 15a3 3 0 0 1 5.66 0" />
                        <circle cx="9" cy="11" r="2" />
                        <rect x="2" y="5" width="20" height="14" rx="2" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Verified identities" subtitle="Verified identity records" :value="number_format($authenticatedDocuments ?? 0)"
                :trend="$verifiedTrend ?? null" :latest="$verifiedLatest ?? null" latest-label="Last verified" :href="$can('identity.review') ? route('admin.identity-verification.index') : null">
                <x-slot:icon>
                    {{-- Lucide: shield-check --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- My workload --}}
        @if ($workload)
            @php
                // Age of the oldest pending item, in whole days. Uses the controller's value when it
                // provides one, otherwise works it out from the item's created date.
                $oldestItem = $workload['oldest_item'] ?? null;
                $oldestDays = null;

                if ($oldestItem) {
                    $oldestDays = $oldestItem['age_days'] ?? ($oldestItem['days'] ?? null);

                    if ($oldestDays === null && !empty($oldestItem['created_at'])) {
                        $oldestDays = (int) abs(\Illuminate\Support\Carbon::parse($oldestItem['created_at'])->diffInDays(now()));
                    }
                }
            @endphp

            <section class="{{ $panel }} min-w-0 p-3 sm:p-5" aria-labelledby="staff-workload-heading">
                <h2 id="staff-workload-heading" class="{{ $panelTitle }}">My workload</h2>
                <p class="{{ $muted }} mt-1 text-sm">
                    @if ($workload['can_review_permits'] || $workload['can_review_identity'])
                        Open items in the queues you can review.
                    @else
                        No review queues are assigned to your account.
                    @endif
                </p>

                <div class="mt-4 grid grid-cols-1 min-[420px]:grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-3">
                    @if ($workload['can_review_permits'])
                        <x-admin.stat-card label="Requests awaiting action" subtitle="Open service requests" :value="$workload['requests']" />
                    @endif

                    @if ($workload['can_review_identity'])
                        <x-admin.stat-card label="Identity reviews in queue" subtitle="Submissions awaiting review" :value="$workload['identity_reviews']" />
                    @endif

                    <x-admin.stat-card label="Oldest pending item" :value="$oldestItem['label'] ?? 'No open items.'"
                        :subtitle="$oldestDays === null ? null : $oldestDays . ($oldestDays === 1 ? ' day old' : ' days old')" />
                </div>
            </section>
        @endif

        {{-- Row 2: bar chart + request status donut --}}
        <div class="grid grid-cols-1 gap-4 sm:gap-6 xl:grid-cols-3">

            {{-- Permit requests per month (always drawn, even when every month is 0) --}}
            <section class="{{ $panel }} min-w-0 p-3 sm:p-5 xl:col-span-2">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="{{ $panelTitle }}">Permit Requests This Year</h2>
                        <p class="{{ $muted }} mt-0.5 text-xs">Requests from all services</p>
                    </div>
                    <x-admin.stat-card label="Last 12 months" subtitle="Permit requests submitted" :value="number_format($participationTotal)" />
                </div>

                <div class="relative mt-4 h-56 w-full min-w-0 sm:h-64">
                    <canvas data-report-chart="trend" data-labels="{{ json_encode($participation['labels']) }}"
                        data-values="{{ json_encode($participation['values']) }}" aria-label="Permit requests per month"
                        role="img"></canvas>
                </div>
            </section>

            {{-- Request status donut --}}
            <section class="{{ $panel }} min-w-0 p-3 sm:p-5">
                <h2 class="{{ $panelTitle }}">Request Status</h2>
                <p class="{{ $muted }} mt-0.5 text-xs">All permit requests</p>

                @if ($requestStatusTotal > 0)
                    <div class="relative mx-auto mt-4 h-44 max-w-48">
                        <canvas data-report-chart="citizens" data-external-legend
                            data-labels="{{ json_encode(['Approved', 'In progress', 'Rejected', 'Cancelled']) }}"
                            data-values="{{ json_encode(array_values($requestStatus)) }}"
                            aria-label="Permit requests by status" role="img"></canvas>
                    </div>
                @else
                    <p class="{{ $muted }} py-12 text-center text-sm">No requests yet.</p>
                @endif

                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ([['Approved', $requestStatus['approved'], 'bg-(--green-500)'], ['In progress', $requestStatus['in_progress'], 'bg-(--amber-500)'], ['Rejected', $requestStatus['rejected'], 'bg-(--red-500)'], ['Cancelled', $requestStatus['cancelled'], 'bg-(--gray-400)']] as [$name, $count, $dot])
                        <li class="flex items-center justify-between">
                            <span class="{{ $muted }} flex items-center gap-2">
                                <span class="{{ $dot }} h-2 w-2 rounded-full"></span>{{ $name }}
                            </span>
                            <span class="{{ $ink }} font-semibold tabular-nums">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Row 3: shortcuts + recent permits --}}
        <div class="grid grid-cols-1 gap-4 sm:gap-6 xl:grid-cols-3">

            {{-- Shortcuts --}}
            <section class="{{ $panel }} self-start">
                <div class="{{ $panelHead }}">
                    <h2 class="{{ $panelTitle }}">Shortcuts</h2>
                    <p class="{{ $muted }} mt-0.5 text-xs">Jump to a module</p>
                </div>

                <div class="space-y-2 p-5">
                    @forelse ($shortcuts as [$routeName, $label])
                        <a href="{{ route($routeName) }}"
                            class="border-(--gray-200) text-(--gray-700) hover:border-(--green-300) hover:bg-(--green-50) dark:text-(--gray-200) dark:hover:border-(--green-500) dark:hover:bg-(--green-500)/10 group flex items-center justify-between rounded-xl border border-dashed px-3 py-3 text-sm font-medium transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm dark:border-white/10">
                            <span>{{ $label }}</span>
                            <span
                                class="text-(--blue-700) group-hover:text-(--green-700) dark:text-(--blue-300) dark:group-hover:text-(--green-400) transition-transform duration-200 group-hover:translate-x-1">&rarr;</span>
                        </a>
                    @empty
                        <p class="{{ $muted }} py-4 text-center text-sm">No shortcuts available.</p>
                    @endforelse
                </div>
            </section>

            {{-- Recent permit requests --}}
            @if ($recentPermits !== null)
                <section class="{{ $panel }} min-w-0 overflow-hidden xl:col-span-2">
                    <div class="{{ $panelHead }} flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="{{ $panelTitle }}">Recent Permit Requests</h2>
                            <p class="{{ $muted }} mt-0.5 text-xs">The latest requests submitted by citizens.</p>
                        </div>

                        <a href="{{ route('admin.permits.index') }}"
                            class="bg-(--gray-100) text-(--gray-700) hover:bg-(--gray-200) dark:text-(--gray-200) shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors dark:bg-white/10 dark:hover:bg-white/20">View all</a>
                    </div>

                    <x-tables.table :heads="['Reference', 'Citizen', 'Permit Type', 'Date', 'Status']" :empty="$recentPermits->isEmpty()" message="No recent permit requests to display."
                        hint="">
                        @foreach ($recentPermits as $permit)
                            <x-tables.row :reference="$permit->reference_number ?? 'REQ-' . $permit->id" :note="$permit->purpose">
                                <td data-reference
                                    class="text-(--gray-900) dark:text-(--gray-50) whitespace-nowrap font-mono text-xs font-medium">
                                    {{ $permit->reference_number ?? 'REQ-' . $permit->id }}
                                </td>
                                <td data-person>
                                    <x-tables.user-cell :name="$permit->user->name ?? ($permit->citizen_name ?? '—')" :avatar="$permit->user->avatar_url ?? null" />
                                </td>
                                <td data-title>{{ $permit->serviceTitle() }}</td>
                                <td data-date class="whitespace-nowrap">{{ $permit->created_at?->format('j M Y') }}</td>
                                <td data-side="right">
                                    <x-tables.badge :variant="match (strtolower($permit->status ?? 'pending')) {
                                        'approved', 'completed', 'authenticated' => 'success',
                                        'rejected', 'denied' => 'danger',
                                        'pending' => 'warning',
                                        'processing', 'under_review' => 'info',
                                        default => 'neutral',
                                    }">
                                        {{ str_replace('_', ' ', strtolower($permit->status ?? 'pending')) }}
                                    </x-tables.badge>
                                </td>
                            </x-tables.row>
                        @endforeach
                    </x-tables.table>
                </section>
            @endif
        </div>
    </div>
@endsection
