@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6" data-reports-page>

        {{-- Welcome the signed-in user and describe the dashboard. --}}
        <x-admin.page-header label="Dashboard" :title="$greeting . ', ' . $firstName . '.'"
            text="Monitor citizen services and barangay transactions." />

        {{-- Warning: approved permits not yet on the blockchain --}}
        @if (($unrecordedPermits ?? 0) > 0 && $can('permits.review'))
            <div
                class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                {{ $unrecordedPermits }} approved permit(s) not confirmed on the blockchain yet.
                Open the permit and press "Record on blockchain".
                <a href="{{ route('admin.permits.index') }}" class="font-semibold underline">Go to Permit Management</a>
            </div>
        @endif

        {{-- Stat cards (Lucide icons) --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card label="Total Citizens" subtitle="Registered accounts" :value="number_format($totalCitizens ?? 0)"
                :trend="$citizensTrend ?? null" :latest="$citizensLatest ?? null" latest-label="Last registered" :href="$isAdmin ? route('admin.users.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="9" cy="7" r="4" />
                        <path d="M2 21a7 7 0 0 1 14 0H2Z" />
                        <circle cx="17" cy="8" r="3" />
                        <path d="M16.5 14.1A6 6 0 0 1 22 20v1h-4.1a9 9 0 0 0-2.8-6.9l1.4 0Z" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Permits" subtitle="Awaiting review" :value="number_format($pendingPermits ?? 0)"
                :trend="$permitsTrend ?? null" :invert="true" :latest="$permitsLatest ?? null" latest-label="Last submitted"
                :href="$can('permits.review') ? route('admin.permits.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M8 3h8a2 2 0 0 1 2 2h1a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h1a2 2 0 0 1 2-2Zm-3 5v12h14V8H5Zm4-3v1h6V5H9Z" />
                        <path d="m8 14 1.4-1.4 2.1 2.1 4.1-4.1L17 12l-5.5 5.5L8 14Z" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Verifications" subtitle="Identity checks" :value="number_format($pendingVerifications ?? 0)"
                :trend="$verificationsTrend ?? null" :invert="true" :latest="$verificationsLatest ?? null" latest-label="Last submitted"
                :href="$can('identity.review') ? route('admin.identity-verification.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm0 2v12h16V6H4Z" />
                        <circle cx="8.5" cy="10" r="2.5" />
                        <path d="M4.5 17a4 4 0 0 1 8 0h-8Z" />
                        <rect x="14" y="9" width="4.5" height="1.5" rx=".75" />
                        <rect x="14" y="13" width="4.5" height="1.5" rx=".75" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            {{-- Text matches DashboardLabelTest --}}
            <x-admin.stat-card label="Verified identities" subtitle="Verified identity records" :value="number_format($authenticatedDocuments ?? 0)"
                :trend="$verifiedTrend ?? null" :latest="$verifiedLatest ?? null" latest-label="Last verified" :href="$can('identity.review') ? route('admin.identity-verification.index') : null">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M12 2 3 5.5V12c0 5.5 3.8 9.5 9 11 5.2-1.5 9-5.5 9-11V5.5L12 2Zm-2.1 10.5 1.4-1.4 1.7 1.7 3.8-3.8 1.4 1.4-5.2 5.2-3.1-3.1Z" />
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
                            <p class="{{ $ink }} mt-1 font-semibold">{{ $workload['oldest_item']['label'] }}</p>
                            <p class="{{ $muted }} mt-1 text-xs">{{ $workload['oldest_item']['age_days'] }} days
                                old</p>
                        @else
                            <p class="{{ $muted }} mt-1 text-sm">No open items.</p>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Row 2: bar chart + request status donut --}}
        <div class="grid gap-6 xl:grid-cols-3">

            {{-- Permit requests per month (always drawn, even when every month is 0) --}}
            <section class="{{ $panel }} p-5 xl:col-span-2">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="{{ $panelTitle }}">Permit Requests This Year</h2>
                        <p class="{{ $muted }} mt-0.5 text-xs">Requests from all services</p>
                    </div>
                    <div class="text-right">
                        <p class="{{ $ink }} text-2xl font-bold tabular-nums">
                            {{ number_format($participationTotal) }}</p>
                        <p class="{{ $muted }} text-xs">Last 12 months</p>
                    </div>
                </div>

                <div class="mt-4 h-64">
                    <canvas data-report-chart="trend" data-labels="{{ json_encode($participation['labels']) }}"
                        data-values="{{ json_encode($participation['values']) }}"
                        aria-label="Permit requests per month" role="img"></canvas>
                </div>
            </section>

            {{-- Request status donut --}}
            <section class="{{ $panel }} p-5">
                <h2 class="{{ $panelTitle }}">Request Status</h2>
                <p class="{{ $muted }} mt-0.5 text-xs">All permit requests</p>

                @if ($requestStatusTotal > 0)
                    <div class="relative mx-auto mt-4 h-44 max-w-48">
                        <canvas data-report-chart="citizens"
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
                            <span
                                class="{{ $ink }} font-semibold tabular-nums">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Row 3: shortcuts + recent permits --}}
        <div class="grid gap-6 xl:grid-cols-3">

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
            <section class="{{ $panel }} min-w-0 overflow-hidden xl:col-span-2">
                <div class="{{ $panelHead }} flex items-start justify-between gap-3">
                    <div>
                        <h2 class="{{ $panelTitle }}">Recent Permit Requests</h2>
                        <p class="{{ $muted }} mt-0.5 text-xs">The latest requests submitted by citizens.</p>
                    </div>

                    @if ($can('permits.review'))
                        <a href="{{ route('admin.permits.index') }}"
                            class="bg-(--gray-100) text-(--gray-700) hover:bg-(--gray-200) dark:text-(--gray-200) shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors dark:bg-white/10 dark:hover:bg-white/20">
                            View all
                        </a>
                    @endif
                </div>

                <x-tables.table :heads="['Reference', 'Citizen', 'Permit Type', 'Date', 'Status']"
                    :empty="$recentPermits->isEmpty()" message="No recent permit requests to display." hint="">
                    @foreach ($recentPermits as $permit)
                        <tr>
                            <td class="whitespace-nowrap font-mono text-xs font-medium text-(--gray-900) dark:text-(--gray-50)">
                                {{ $permit->reference_number ?? 'REQ-' . $permit->id }}
                            </td>
                            <td>
                                <x-tables.user-cell :name="$permit->user->name ?? ($permit->citizen_name ?? '—')"
                                    :avatar="$permit->user->avatar_url ?? null" />
                            </td>
                            <td>{{ $permit->serviceTitle() }}</td>
                            <td class="whitespace-nowrap">{{ $permit->created_at?->format('j M Y') }}</td>
                            <td>
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
                        </tr>
                    @endforeach
                </x-tables.table>
            </section>
        </div>
    </div>
@endsection