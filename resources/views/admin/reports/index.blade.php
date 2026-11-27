@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    @php
        // Which filters make sense on which tab (everything else is hidden).
        $tabFilters = [
            'service' => ['permit_type', 'application_status', 'assigned_staff'],
            'citizens' => ['identity_status'],
            'verification' => ['permit_type', 'blockchain_status'],
            'staff' => ['permit_type', 'assigned_staff'],
        ];
        $ledgerBtn = 'inline-flex h-10 items-center whitespace-nowrap rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700';
        $statBox = 'grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900';
        $scopeAll = 'rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300';
        $scopePeriod = 'rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300';
    @endphp

    <div class="admin-module space-y-5" data-reports-page>
        {{-- Page header with export actions (admins only). Export links carry the active tab and filters. --}}
        <x-admin.page-header title="Reports & Analytics"
            text="A clear view of service delivery and document verification." export-label="CSV (opens in Excel)"
            :export-url="$isStaff
                ? null
                : route('admin.exports.show', ['module' => 'reports', 'format' => 'csv'] + $exportQuery)">

            @unless ($isStaff)
                @foreach (['xlsx' => 'Excel', 'pdf' => 'PDF'] as $format => $formatLabel)
                    <a href="{{ route('admin.exports.show', ['module' => 'reports', 'format' => $format] + $exportQuery) }}"
                        data-title="Export this report?"
                        data-text="The file may contain personal information. Keep it safe and share it only with authorized people."
                        onclick="event.preventDefault(); var a = this; tConfirmation({ title: a.dataset.title, text: a.dataset.text, confirmLabel: 'Yes, export' }).then(function (ok) { if (ok) window.location = a.href; });"
                        class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">{{ $formatLabel }}</a>
                @endforeach
            @endunless
        </x-admin.page-header>

        @if ($isStaff)
            {{-- Staff accounts see workload-only metrics. --}}
            <section aria-label="My staff access metrics" class="{{ $statBox }} lg:grid-cols-4">
                @foreach ([
                    ['My workload', $staffAccess['my_workload'], 'Your cases opened in the last 30 days'],
                    ['Requests awaiting action', $staffAccess['awaiting_action'], 'Pending or in progress'],
                    ['Identity reviews in queue', $staffAccess['identity_reviews_in_queue'], 'Submissions awaiting a decision'],
                    ['Oldest pending request age', $staffAccess['oldest_pending_age_days'] === null ? '—' : $staffAccess['oldest_pending_age_days'] . ' days', 'Time since submission'],
                ] as [$metricLabel, $metricValue, $metricSubtitle])
                    <x-admin.stat-card :label="$metricLabel" :value="$metricValue" :subtitle="$metricSubtitle" />
                @endforeach
            </section>
        @else
            {{-- ───────── Insights (above the tabs, only when there is something to show) ───────── --}}
            @if (! empty($insights))
                @php
                    $kindLabels = [
                        'rule' => 'Rule-based alert',
                        'descriptive' => 'Descriptive analytics',
                        'predictive' => 'Predictive forecast',
                    ];
                    $severityClasses = [
                        'critical' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200',
                        'warning' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200',
                        'positive' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200',
                        'info' => 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200',
                    ];
                @endphp
                <section aria-label="Insights" class="space-y-2">
                    @foreach ($insights as $insight)
                        @php
                            $severity = $insight['severity'] ?? 'info';
                            $kind = $insight['type'] ?? 'rule';
                        @endphp
                        <article class="{{ $severityClasses[$severity] ?? $severityClasses['info'] }} flex flex-wrap items-start justify-between gap-3 rounded-xl border px-4 py-3 text-sm">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">{{ $insight['title'] ?? ucfirst($severity) }}</h3>
                                    <span class="rounded-full bg-white/70 px-2 py-0.5 text-xs font-medium dark:bg-slate-900/60">{{ $kindLabels[$kind] ?? $kindLabels['rule'] }}</span>
                                </div>
                                <p class="mt-1">{{ $insight['message'] }}</p>
                                @if (! empty($insight['stats']))
                                    <dl class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs">
                                        @foreach ($insight['stats'] as $statName => $statValue)
                                            <div><dt class="inline opacity-75">{{ $statName }}:</dt> <dd class="inline font-semibold tabular-nums">{{ $statValue }}</dd></div>
                                        @endforeach
                                    </dl>
                                @endif
                            </div>
                            @if (! empty($insight['link']))
                                <a href="{{ $insight['link'] }}"
                                    class="{{ $focus }} shrink-0 font-semibold underline underline-offset-2">{{ $insight['action'] ?? 'Review' }}</a>
                            @endif
                        </article>
                    @endforeach
                </section>
            @endif

            <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-slate-900">
                {{-- Tabs on the left; filters and shortcuts on the right. --}}
                <x-tables.toolbar key="report" class="mb-3"
                    :tabs="['service' => 'Service Requests', 'citizens' => 'Citizens', 'verification' => 'Verification', 'staff' => 'Staff Performance']">
                    <x-slot:searchActions>
                        <details class="relative">
                            <summary
                                class="{{ $focus }} inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 transition marker:content-none hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden">
                                <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 3H2l8 9.46V19l4 2v-8.54z" />
                                </svg>
                                Filters
                            </summary>
                            <div
                                class="absolute right-0 z-30 mt-2 max-h-[80vh] w-[min(90vw,40rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                @if ($errors->any())
                                    <p role="alert" class="mb-3 text-sm text-rose-700 dark:text-rose-300">Please correct the filters.</p>
                                @endif
                                <form method="GET" action="{{ route('admin.reports.index') }}" class="space-y-4">
                                    <input type="hidden" name="report_tab" value="{{ $tab }}">

                                    <div class="flex flex-wrap gap-2" role="group" aria-label="Date presets">
                                        @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'last30' => 'Last 30 Days', 'quarter' => 'This Quarter', 'year' => 'This Year'] as $preset => $presetLabel)
                                            <button type="button" data-report-preset="{{ $preset }}"
                                                class="{{ $focus }} rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">{{ $presetLabel }}</button>
                                        @endforeach
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="report-from"
                                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">From (custom range)</label>
                                            <input id="report-from" name="from" type="date"
                                                value="{{ $filters['from']->toDateString() }}" required
                                                class="admin-field">
                                            @error('from')
                                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="report-to"
                                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">To</label>
                                            <input id="report-to" name="to" type="date"
                                                value="{{ $filters['to']->toDateString() }}" required class="admin-field">
                                            @error('to')
                                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        @php
                                            $selects = [
                                                'permit_type' => ['Permit type', 'All permit types', collect(config('barangay_services.services', []))->pluck('title', 'type')->all()],
                                                'application_status' => ['Application status', 'All statuses', $filterOptions['application_status']],
                                                'assigned_staff' => ['Assigned staff', 'All staff', $filterOptions['staff']->pluck('name', 'id')->all()],
                                                'identity_status' => ['Identity verification status', 'All statuses', $filterOptions['identity_status']],
                                                'blockchain_status' => ['Blockchain transaction status', 'All statuses', $filterOptions['blockchain_status']],
                                            ];
                                        @endphp
                                        @foreach ($selects as $name => [$selectTitle, $selectAll, $selectOptions])
                                            @if (in_array($name, $tabFilters[$tab], true))
                                                <div>
                                                    <label for="report-{{ $name }}"
                                                        class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $selectTitle }}</label>
                                                    <select id="report-{{ $name }}" name="{{ $name }}" class="admin-field">
                                                        <option value="">{{ $selectAll }}</option>
                                                        @foreach ($selectOptions as $optionValue => $optionLabel)
                                                            <option value="{{ $optionValue }}" @selected((string) ($filters[$name] ?? '') === (string) $optionValue)>{{ $optionLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error($name)
                                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            @endif
                                        @endforeach

                                        <div>
                                            <label for="report-grouping"
                                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Grouping</label>
                                            <select id="report-grouping" name="grouping" class="admin-field">
                                                @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $title)
                                                    <option value="{{ $value }}" @selected($filters['grouping'] === $value)>{{ $title }}</option>
                                                @endforeach
                                            </select>
                                            @error('grouping')
                                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <a href="{{ route('admin.reports.index', ['report_tab' => $tab]) }}"
                                            class="{{ $focus }} rounded-lg px-3 py-2 text-sm font-medium text-slate-600 underline underline-offset-2 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Reset filters</a>
                                        <button type="submit"
                                            class="{{ $focus }} {{ $ledgerBtn }}">Apply filters</button>
                                    </div>
                                </form>
                            </div>
                        </details>

                        @if ($tab === 'service')
                            <a href="{{ route('admin.permits.index') }}" class="{{ $focus }} {{ $ledgerBtn }}">Permit request list</a>
                        @endif
                    </x-slot:searchActions>
                </x-tables.toolbar>

                {{-- ───────── Service Requests ───────── --}}
                @if ($tab === 'service')
                    <div class="space-y-3 rounded-2xl">
                        <h2 class="sr-only">Service Requests</h2>

                        <div class="{{ $statBox }} lg:grid-cols-4">
                            @foreach ($metricCards as [$metricLabel, $metricValue, $comparisonLabel])
                                <x-admin.stat-card :label="$metricLabel" :value="$metricValue" :subtitle="$comparisonLabel"
                                    :href="route('admin.permits.index')" />
                            @endforeach
                        </div>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <article class="{{ $card }} lg:col-span-7">
                                <h3 class="{{ $heading }}">Application Trends</h3>
                                <p class="{{ $muted }}">{{ ucfirst($trends['granularity']) }} applications</p>
                                <x-partials.chart type="trend" :empty="$summary['total'] === 0"
                                    :labels="$trends['labels']" :values="$trends['total']" unit="requests"
                                    :aria="'Application trends, ' . $trends['granularity'] . ' grouping'" />
                            </article>

                            <article class="{{ $card }} lg:col-span-5">
                                <h3 class="{{ $heading }}">Most Requested Services</h3>
                                <p class="{{ $muted }}">Requests per permit type, highest first</p>
                                <x-partials.chart type="permit-type" :empty="$summary['total'] === 0"
                                    :labels="array_column($by_permit_type, 'label')"
                                    :values="array_column($by_permit_type, 'value')" unit="requests"
                                    aria="Applications by permit type" />
                            </article>
                        </div>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <article class="{{ $card }} lg:col-span-5">
                                <h3 class="{{ $heading }}">Application Status</h3>
                                <p class="{{ $muted }}">Share of requests by current status</p>
                                <x-partials.chart type="status" center="requests"
                                    :empty="($summary['pending'] + $summary['processing'] + $summary['approved'] + $summary['rejected'] + $summary['cancelled']) === 0"
                                    :labels="['Pending', 'Processing', 'Approved', 'Rejected', 'Cancelled']"
                                    :values="[$summary['pending'], $summary['processing'], $summary['approved'], $summary['rejected'], $summary['cancelled']]"
                                    height="h-72" aria="Application status distribution" />
                            </article>

                            <article class="{{ $card }} lg:col-span-7">
                                <h3 class="{{ $heading }}">Average Processing Time by Permit Type</h3>
                                <p class="{{ $muted }}">Hours from submission to decision</p>
                                @isset($processing_by_type)
                                    <x-partials.chart type="processing-by-type" height="h-72" unit="hrs"
                                        :empty="empty($processing_by_type)"
                                        :labels="array_column($processing_by_type, 'label')"
                                        :values="array_column($processing_by_type, 'hours')"
                                        aria="Average processing time by permit type" />
                                @else
                                    <p class="{{ $muted }} mt-3">Not available yet.</p>
                                @endisset
                            </article>
                        </div>

                        {{-- Service levels: rates, backlog and the oldest open request. --}}
                        <div class="{{ $statBox }} lg:grid-cols-4">
                            <x-admin.stat-card label="Approval rate"
                                :value="$summary['decisions'] > 0 ? number_format($summary['approval_rate'], 1) . '%' : '—'"
                                :subtitle="'Of ' . $summary['decisions'] . ' approved or rejected requests'" />
                            <x-admin.stat-card label="Rejection rate"
                                :value="$summary['decisions'] > 0 ? number_format($summary['rejection_rate'], 1) . '%' : '—'"
                                :subtitle="'Of ' . $summary['decisions'] . ' approved or rejected requests'" />
                            <x-admin.stat-card label="Overdue applications"
                                :value="$summary['overdue'] ?? '—'" subtitle="Past the configured deadline"
                                :href="route('admin.permits.index')" />
                            <x-admin.stat-card label="Oldest pending request"
                                :value="isset($summary['oldest_pending']) ? $summary['oldest_pending']['age_days'] . ' days' : '—'"
                                :subtitle="$summary['oldest_pending']['reference'] ?? 'No pending requests'" />
                        </div>
                    </div>
                @endif

                {{-- ───────── Citizens ───────── --}}
                @if ($tab === 'citizens')
                    <div class="space-y-3 rounded-2xl">
                        <h2 class="sr-only">Citizens</h2>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <article class="{{ $card }} lg:col-span-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="{{ $heading }}">Identity Status</h3>
                                    <span class="{{ $scopeAll }}">All-time</span>
                                </div>
                                <p class="{{ $muted }}">Every registered citizen account</p>
                                <x-partials.chart type="citizens" center="citizens" height="h-72"
                                    :empty="$citizens['total'] === 0"
                                    :labels="['Verified', 'Pending', 'Rejected', 'Unverified']"
                                    :values="[$citizens['verified'], $citizens['pending'], $citizens['rejected'], $citizens['unverified']]"
                                    :aria="'Identity status: ' . $citizens['verified'] . ' verified, ' . $citizens['pending'] . ' pending, ' . $citizens['rejected'] . ' rejected, ' . $citizens['unverified'] . ' unverified'" />
                            </article>

                            <article class="{{ $card }} lg:col-span-7">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="{{ $heading }}">Citizen Registrations</h3>
                                    <span class="{{ $scopePeriod }}">Selected period</span>
                                </div>
                                <p class="{{ $muted }}">New accounts, {{ $filters['grouping'] }}</p>
                                @isset($citizens['registration_trend'])
                                    <x-partials.chart type="registrations" height="h-72" unit="citizens"
                                        :empty="array_sum($citizens['registration_trend']['values']) === 0"
                                        :labels="$citizens['registration_trend']['labels']"
                                        :values="$citizens['registration_trend']['values']"
                                        aria="Citizen registrations over time" />
                                @else
                                    <p class="{{ $muted }} mt-3">Not available yet.</p>
                                @endisset
                            </article>
                        </div>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                            <article class="{{ $card }} space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="{{ $heading }}">All-time totals</h3>
                                    <span class="{{ $scopeAll }}">All-time</span>
                                </div>
                                <div class="{{ $statBox }}">
                                    <x-admin.stat-card label="Registered citizens" subtitle="All citizen accounts" :value="$citizens['total']" />
                                    <x-admin.stat-card label="Verification completion"
                                        :value="$citizens['total'] ? number_format(($citizens['verified'] / $citizens['total']) * 100, 1) . '%' : '—'"
                                        subtitle="Verified out of all accounts" />
                                </div>
                            </article>

                            <article class="{{ $card }} space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="{{ $heading }}">Identity Review Rates</h3>
                                    <span class="{{ $scopePeriod }}">Selected period</span>
                                </div>
                                <p class="{{ $muted }}">{{ $citizens['review_period_label'] }} ·
                                    {{ $citizens['review_count'] }} reviewed decisions
                                    @isset($citizens['new_in_period'])
                                        · {{ $citizens['new_in_period'] }} newly registered
                                    @endisset
                                </p>
                                @foreach ([['Approval', $citizens['approval_rate'], 'bg-emerald-600'], ['Rejection', $citizens['rejection_rate'], 'bg-rose-600']] as [$rateLabel, $rate, $rateColor])
                                    <div>
                                        <x-admin.stat-card :label="$rateLabel" :value="$citizens['review_count'] ? number_format($rate, 1) . '%' : '—'" :subtitle="$citizens['review_count'] ? 'Share of identity decisions' : 'No decisions'" />
                                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                            <div class="{{ $rateColor }} h-full rounded-full"
                                                style="width: {{ min(100, max(0, $rate)) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </article>
                        </div>
                    </div>
                @endif

                {{-- ───────── Verification ───────── --}}
                @if ($tab === 'verification')
                    <div class="space-y-3 rounded-2xl">
                        {{-- Blockchain --}}
                        <section aria-labelledby="ledger-heading">
                            <article class="{{ $card }} space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h2 id="ledger-heading" class="{{ $heading }}">Blockchain Transactions</h2>
                                        <p class="{{ $muted }}">Confirms a document record was written to the ledger. This is not the same as a document passing verification.</p>
                                    </div>
                                    <a href="{{ route('admin.blockchain-ledger.index') }}" class="{{ $focus }} {{ $ledgerBtn }}">Open ledger</a>
                                </div>

                                <div class="{{ $statBox }} lg:grid-cols-5">
                                    <x-admin.stat-card label="Total" subtitle="All transaction statuses" :value="$blockchain['total']" />
                                    @foreach ([['Successful', $blockchain['successful'], 'bg-emerald-600'], ['Failed', $blockchain['failed'], 'bg-rose-600'], ['Pending', $blockchain['pending'], 'bg-amber-500'], ['Revoked', $blockchain['revoked'], 'bg-slate-500']] as [$stateLabel, $stateCount, $stateColor])
                                        <x-admin.stat-card :label="$stateLabel" :value="$stateCount" subtitle="Ledger transactions">
                                            <x-slot:icon><span class="{{ $stateColor }} size-2 rounded-full"></span></x-slot:icon>
                                        </x-admin.stat-card>
                                    @endforeach
                                </div>

                                @isset($blockchain['contract'])
                                    <div>
                                        <p class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-200">Smart contract executions</p>
                                        <div class="{{ $statBox }} lg:grid-cols-4">
                                            @foreach ($blockchain['contract'] as [$contractLabel, $contractValue, $contractSubtitle])
                                                <x-admin.stat-card :label="$contractLabel" :value="$contractValue" :subtitle="$contractSubtitle" />
                                            @endforeach
                                        </div>
                                    </div>
                                @endisset

                                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                    <h3 class="{{ $heading }}">Transaction Activity</h3>
                                    <p class="{{ $muted }}">Transactions by status over time</p>
                                    @isset($blockchain['activity'])
                                        <x-partials.chart type="activity" stacked unit="transactions"
                                            :empty="$blockchain['total'] === 0"
                                            :labels="$blockchain['activity']['labels']"
                                            :datasets="$blockchain['activity']['datasets']"
                                            aria="Blockchain transaction activity over time" />
                                    @else
                                        <p class="{{ $muted }} mt-3">Not available yet.</p>
                                    @endisset
                                </div>

                                {{-- Recent transactions --}}
                                <x-tables.card :searchable="false" :per-page-options="[]">
                                    <x-tables.toolbar key="report-transactions" :total="count($blockchain['recent'])"
                                        total-label="Recent Transactions" />

                                    <x-tables.table :heads="['Reference', 'Type', 'Status', 'Transaction', 'Date']"
                                        :empty="empty($blockchain['recent'])"
                                        message="No transactions in this period.">
                                        @foreach ($blockchain['recent'] as $transaction)
                                            <tr>
                                                <td data-title>{{ $transaction['reference_number'] }}</td>
                                                <td data-label="Type">{{ $transaction['document_type'] }}</td>
                                                <td data-side="right">
                                                    <x-tables.badge class="gap-1.5 rounded-full!"
                                                        :variant="match ($transaction['status']) {
                                                            'Successful' => 'success',
                                                            'Failed' => 'danger',
                                                            'Pending' => 'warning',
                                                            'Revoked' => 'neutral',
                                                            default => 'info',
                                                        }">
                                                        <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                                        {{ $transaction['status'] }}
                                                    </x-tables.badge>
                                                </td>
                                                <td data-label="Transaction">
                                                    @if ($transaction['explorer_url'])
                                                        <a href="{{ $transaction['explorer_url'] }}" target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="{{ $focus }} text-blue-700 underline dark:text-blue-300">{{ $transaction['transaction_hash'] ?? 'View transaction' }}</a>
                                                    @else
                                                        {{ $transaction['transaction_hash'] ?? '—' }}
                                                    @endif
                                                </td>
                                                <td data-label="Date">{{ $transaction['date']?->format('M j, Y') ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </x-tables.table>
                                </x-tables.card>
                            </article>
                        </section>

                        {{-- QR document verification --}}
                        <section aria-labelledby="documents-heading">
                            <article class="{{ $card }} space-y-4">
                                <div>
                                    <h2 id="documents-heading" class="{{ $heading }}">Document Verification</h2>
                                    <p class="{{ $muted }}">Whether issued documents pass authentication when their QR code is checked</p>
                                </div>

                                <div class="{{ $statBox }} lg:grid-cols-5">
                                    <x-admin.stat-card label="Documents Issued" subtitle="Issued in the selected period" :value="$documents['documents_issued']" />
                                    <x-admin.stat-card label="Verification attempts" subtitle="Logged submissions in the period" :value="$documents['verification_attempts']" />
                                    <x-admin.stat-card label="Valid rate" :subtitle="$documents['verification_attempts'] ? 'Share of attempts that passed' : 'No attempts'" :value="$documents['verification_attempts'] ? number_format($documents['valid_rate'], 1) . '%' : '—'" />
                                    <x-admin.stat-card label="Documents Revoked" subtitle="Revoked in the selected period" :value="$documents['documents_revoked']" />
                                    <x-admin.stat-card label="Never verified" subtitle="Issued, no logged attempt" :value="$documents['without_verification'] ?? '—'" />
                                </div>

                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Attempts by result</h3>
                                        <x-partials.chart type="documents" center="attempts"
                                            :empty="($documents['valid'] + $documents['invalid'] + $documents['revoked'] + $documents['other']) === 0"
                                            :labels="['Valid', 'Invalid', 'Revoked', 'Other']"
                                            :values="[$documents['valid'], $documents['invalid'], $documents['revoked'], $documents['other']]"
                                            aria="Verification attempts by result" />
                                    </div>

                                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Latest result per issued request</p>
                                        {{-- Auto-fit grid so any number of cards (7 here) wraps cleanly without overflow. --}}
                                        <div class="mt-2 grid grid-cols-[repeat(auto-fit,minmax(8.5rem,1fr))] gap-2">
                                            @foreach ($latestResults as $resultKey => $resultCount)
                                                <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                                                    <x-admin.stat-card :label="ucfirst(str_replace('_', ' ', $resultKey))" :value="$resultCount" subtitle="Latest logged result" />
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">For documents issued in the selected period, these counts use the most recent logged verification, including later attempts. Requests without a logged result are excluded.</p>
                                    </div>
                                </div>
                            </article>
                        </section>
                    </div>
                @endif

                {{-- ───────── Staff Performance (admins only) ───────── --}}
                @if ($tab === 'staff')
                    <div class="space-y-3 rounded-2xl">
                        <h2 class="sr-only">Staff Performance</h2>
                        <p class="{{ $muted }}">Decisions completed in the selected period. The table lists the top 10 staff.</p>

                        @isset($staff_performance['totals'])
                            @php $totals = $staff_performance['totals']; @endphp
                            <div class="{{ $statBox }} lg:grid-cols-6">
                                <x-admin.stat-card label="Processed" subtitle="Approved or rejected" :value="$totals['processed']" />
                                <x-admin.stat-card label="Approved" subtitle="Decisions in period" :value="$totals['approved']" />
                                <x-admin.stat-card label="Rejected" subtitle="Decisions in period" :value="$totals['rejected']" />
                                <x-admin.stat-card label="Pending workload" subtitle="Awaiting action" :value="$totals['pending_workload']" />
                                <x-admin.stat-card label="Overdue" subtitle="Past the deadline" :value="$totals['overdue']" />
                                <x-admin.stat-card label="Avg. processing time" subtitle="Submission to decision"
                                    :value="$totals['average_processing_hours'] === null ? '—' : number_format($totals['average_processing_hours'], 2) . ' hrs'" />
                            </div>
                        @endisset

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <article class="{{ $card }} lg:col-span-5">
                                <h3 class="{{ $heading }}">Workload Distribution</h3>
                                <p class="{{ $muted }}">Open requests assigned to each staff member</p>
                                @isset($staff_performance['workload'])
                                    <x-partials.chart type="staff-workload" unit="open" uniform
                                        :empty="array_sum($staff_performance['workload']['values']) === 0"
                                        :labels="$staff_performance['workload']['labels']"
                                        :values="$staff_performance['workload']['values']"
                                        aria="Open requests per staff member" />
                                @else
                                    <p class="{{ $muted }} mt-3">Not available yet.</p>
                                @endisset
                            </article>

                            <article class="{{ $card }} lg:col-span-7">
                                <h3 class="{{ $heading }}">Performance Comparison</h3>
                                <p class="{{ $muted }}">Approved and rejected decisions per staff member</p>
                                @isset($staff_performance['comparison'])
                                    <x-partials.chart type="staff-comparison" unit="decisions"
                                        :empty="empty($staff_performance['staff'])"
                                        :labels="$staff_performance['comparison']['labels']"
                                        :datasets="$staff_performance['comparison']['datasets']"
                                        aria="Approved and rejected decisions per staff member" />
                                @else
                                    <p class="{{ $muted }} mt-3">Not available yet.</p>
                                @endisset
                            </article>
                        </div>

                        <p class="{{ $muted }}">Completed decisions without a recorded processor: {{ $staff_performance['unattributed'] ?? 0 }}</p>

                        {{-- Click a column heading to sort. --}}
                        <div data-report-sortable class="[&_th]:cursor-pointer [&_th]:select-none [&_th[aria-sort=ascending]]:underline [&_th[aria-sort=descending]]:underline">
                            <x-tables.card :searchable="false" :per-page-options="[]">
                                <x-tables.toolbar key="report-staff-performance"
                                    :total="count($staff_performance['staff'])" total-label="Staff shown"
                                    :search="true" search-placeholder="Search staff..." />

                                <x-tables.table
                                    :heads="['Staff member', 'Processed', 'Approved', 'Rejected', 'Avg. processing time']"
                                    :empty="empty($staff_performance['staff'])"
                                    message="No staff data in this period.">
                                    @foreach ($staff_performance['staff'] as $staff)
                                        <tr>
                                            <td data-title><x-tables.user-cell :name="$staff['staff_name']" /></td>
                                            <td data-label="Processed">{{ $staff['processed'] }}</td>
                                            <td data-label="Approved">{{ $staff['approved'] }}</td>
                                            <td data-label="Rejected">{{ $staff['rejected'] }}</td>
                                            <td data-label="Avg. processing time">{{ $staff['average_processing_hours'] === null ? '—' : number_format($staff['average_processing_hours'], 2) . ' hrs' }}</td>
                                        </tr>
                                    @endforeach
                                </x-tables.table>
                            </x-tables.card>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection