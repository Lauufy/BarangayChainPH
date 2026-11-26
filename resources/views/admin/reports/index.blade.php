@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    <div class="admin-module space-y-5" data-reports-page>
        {{-- Page header with export actions (admins only). --}}
        <x-admin.page-header title="Reports & Analytics"
            text="A clear view of service delivery and document verification." export-label="CSV (opens in Excel)"
            :export-url="$isStaff
                ? null
                : route('admin.exports.show', ['module' => 'reports', 'format' => 'csv'] + $exportQuery)">

            @unless ($isStaff)
                <a href="{{ route('admin.exports.show', ['module' => 'reports', 'format' => 'pdf'] + $exportQuery) }}"
                    data-title="Export this report?"
                    data-text="The file may contain personal information. Keep it safe and share it only with authorized people."
                    onclick="event.preventDefault(); var a = this; tConfirmation({ title: a.dataset.title, text: a.dataset.text, confirmLabel: 'Yes, export' }).then(function (ok) { if (ok) window.location = a.href; });"
                    class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">PDF</a>
            @endunless
        </x-admin.page-header>

        @if ($isStaff)
            {{-- Staff accounts see workload-only metrics. --}}
            <section aria-label="My staff access metrics" class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-4">
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
            {{-- Insights: only shown when there is something to act on. --}}
            @if (count($insights))
                <section aria-label="Insights" class="space-y-2">
                    @foreach ($insights as $insight)
                        <div @class([
                            'flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-2.5 text-sm',
                            'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200' =>
                                $insight['severity'] === 'critical',
                            'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200' =>
                                $insight['severity'] !== 'critical',
                        ])>
                            <p>{{ $insight['message'] }}</p>
                            @if ($insight['link'])
                                <a href="{{ $insight['link'] }}"
                                    class="{{ $focus }} font-semibold underline underline-offset-2">Review</a>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif

            <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-slate-900">
                {{-- Tabs on the left; filters and permit list shortcut on the right. --}}
                <x-tables.toolbar key="report" class="mb-3"
                    :tabs="['service' => 'Service Requests', 'citizens' => 'Citizens', 'verification' => 'Verification']">
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
                                class="absolute right-0 z-30 mt-2 w-[min(90vw,40rem)] rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                @if ($errors->any())
                                    <p role="alert" class="mb-3 text-sm text-rose-700 dark:text-rose-300">Please correct the filters.</p>
                                @endif
                                <form method="GET" action="{{ route('admin.reports.index') }}" class="space-y-4">
                                    <input type="hidden" name="report_tab"
                                        value="{{ in_array(request('report_tab'), ['citizens', 'verification'], true) ? request('report_tab') : 'service' }}">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="report-from"
                                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">From</label>
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
                                        <div>
                                            <label for="report-permit-type"
                                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Permit type</label>
                                            <select id="report-permit-type" name="permit_type" class="admin-field">
                                                <option value="">All permit types</option>
                                                @foreach (config('barangay_services.services', []) as $service)
                                                    <option value="{{ $service['type'] }}" @selected($filters['permit_type'] === $service['type'])>
                                                        {{ $service['title'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="report-grouping"
                                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Grouping</label>
                                            <select id="report-grouping" name="grouping" class="admin-field">
                                                @foreach (['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $title)
                                                    <option value="{{ $value }}" @selected($filters['grouping'] === $value)>
                                                        {{ $title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @error('permit_type')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                    @error('grouping')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex flex-wrap gap-2" role="group" aria-label="Date presets">
                                            <button type="button" data-report-preset="month"
                                                class="{{ $focus }} rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">This Month</button>
                                            <button type="button" data-report-preset="last30"
                                                class="{{ $focus }} rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">Last 30 Days</button>
                                        </div>
                                        <button type="submit"
                                            class="{{ $focus }} inline-flex h-10 items-center whitespace-nowrap rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Apply filters</button>
                                    </div>
                                </form>
                            </div>
                        </details>

                        @if (! in_array(request('report_tab'), ['citizens', 'verification'], true))
                            <a href="{{ route('admin.permits.index') }}"
                                class="{{ $focus }} inline-flex h-10 items-center whitespace-nowrap rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Permit request list</a>
                        @endif
                    </x-slot:searchActions>
                </x-tables.toolbar>

                {{-- ───────── Service Requests ───────── --}}
                @if (! in_array(request('report_tab'), ['citizens', 'verification'], true))
                    <div class="space-y-3 rounded-2xl">
                        <h2 class="sr-only">Service Requests</h2>

                        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-4">
                            @foreach ($metricCards as [$metricLabel, $metricValue, $comparisonLabel])
                                <x-admin.stat-card :label="$metricLabel" :value="$metricValue" :subtitle="$comparisonLabel"
                                    :href="route('admin.permits.index')" />
                            @endforeach
                        </div>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <article class="{{ $card }} lg:col-span-7">
                                <h3 class="{{ $heading }}">Application Trends</h3>
                                <p class="{{ $muted }}">{{ ucfirst($trends['granularity']) }} applications</p>
                                @if ($summary['total'] > 0)
                                    <div class="mt-3 h-64">
                                        <canvas data-report-chart="trend"
                                            data-labels="{{ json_encode($trends['labels']) }}"
                                            data-values="{{ json_encode($trends['total']) }}"
                                            aria-label="Application trends, {{ $trends['granularity'] }} grouping"
                                            role="img"></canvas>
                                    </div>
                                @else
                                    <p class="{{ $muted }} mt-3">No data in this period.</p>
                                @endif
                            </article>

                            <article class="{{ $card }} lg:col-span-5">
                                <h3 class="{{ $heading }}">Applications by Permit Type</h3>
                                <p class="{{ $muted }}">Share of requests per service</p>
                                @if ($summary['total'] > 0)
                                    <div class="mt-3 h-64">
                                        <canvas data-report-chart="permit-type"
                                            data-labels="{{ json_encode(array_column($by_permit_type, 'label')) }}"
                                            data-values="{{ json_encode(array_column($by_permit_type, 'value')) }}"
                                            aria-label="Applications by permit type" role="img"></canvas>
                                    </div>
                                @else
                                    <p class="{{ $muted }} mt-3">No data in this period.</p>
                                @endif
                            </article>
                        </div>
                    </div>
                @endif

                {{-- ───────── Citizens ───────── --}}
                @if (request('report_tab') === 'citizens')
                    <div class="space-y-3 rounded-2xl">
                        <h2 class="sr-only">Citizen Verification</h2>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <article class="{{ $card }} lg:col-span-7">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <h3 class="{{ $heading }}">Identity Status</h3>
                                    <x-admin.stat-card label="Registered citizens" subtitle="All citizen accounts" :value="$citizens['total']" />
                                </div>
                                @if ($citizens['total'] > 0)
                                    <div class="mx-auto mt-2 h-48 max-w-xs">
                                        <canvas data-report-chart="citizens"
                                            data-labels="{{ json_encode(['Verified', 'Pending', 'Rejected', 'Unverified']) }}"
                                            data-values="{{ json_encode([$citizens['verified'], $citizens['pending'], $citizens['rejected'], $citizens['unverified']]) }}"
                                            aria-label="Identity status: {{ $citizens['verified'] }} verified, {{ $citizens['pending'] }} pending, {{ $citizens['rejected'] }} rejected, {{ $citizens['unverified'] }} unverified"
                                            role="img"></canvas>
                                    </div>
                                @else
                                    <p class="{{ $muted }} mt-3">No data in this period.</p>
                                @endif
                            </article>

                            <article class="{{ $card }} space-y-4 lg:col-span-5">
                                <div>
                                    <h3 class="{{ $heading }}">Identity Review Rates</h3>
                                    <p class="{{ $muted }}">{{ $citizens['review_period_label'] }} ·
                                        {{ $citizens['review_count'] }} reviewed decisions</p>
                                </div>
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

                        {{-- Request status with proportional bar. --}}
                        <article class="{{ $card }} space-y-3">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h3 class="{{ $heading }}">Request status</h3>
                                <x-admin.stat-card label="Approved"
                                    :value="$summary['decisions'] > 0 ? number_format($summary['approval_rate'], 1) . '%' : '—'"
                                    :subtitle="'Based on ' . $summary['decisions'] . ' approved or rejected requests'" />
                            </div>

                            @if ($summary['pending'] + $summary['processing'] + $summary['approved'] + $summary['rejected'] + $summary['cancelled'] > 0)
                                <div class="flex h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                    role="img" aria-label="Share of requests by status">
                                    @foreach ([[$summary['pending'], 'bg-amber-500'], [$summary['processing'], 'bg-blue-600'], [$summary['approved'], 'bg-emerald-600'], [$summary['rejected'], 'bg-rose-600'], [$summary['cancelled'], 'bg-slate-500']] as [$segValue, $segColor])
                                        @if ($segValue > 0)
                                            <span class="{{ $segColor }}" style="flex: {{ $segValue }} 1 0%"></span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-5">
                                @foreach ([['Pending', $summary['pending'], 'bg-amber-500'], ['Processing', $summary['processing'], 'bg-blue-600'], ['Approved', $summary['approved'], 'bg-emerald-600'], ['Rejected', $summary['rejected'], 'bg-rose-600'], ['Cancelled', $summary['cancelled'], 'bg-slate-500']] as [$statusLabel, $statusCount, $statusColor])
                                    <x-admin.stat-card :label="$statusLabel" :value="$statusCount" subtitle="Service requests">
                                        <x-slot:icon><span class="{{ $statusColor }} size-2 rounded-full"></span></x-slot:icon>
                                    </x-admin.stat-card>
                                @endforeach
                            </div>
                        </article>
                    </div>
                @endif

                {{-- ───────── Verification ───────── --}}
                @if (request('report_tab') === 'verification')
                    <div class="space-y-3 rounded-2xl">
                        {{-- Blockchain --}}
                        <section aria-labelledby="ledger-heading">
                            <article class="{{ $card }} space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h2 id="ledger-heading" class="{{ $heading }}">Blockchain Transactions</h2>
                                        <p class="{{ $muted }}">Document records anchored on the ledger</p>
                                    </div>
                                    <a href="{{ route('admin.blockchain-ledger.index') }}"
                                        class="{{ $focus }} inline-flex h-10 items-center whitespace-nowrap rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Open ledger</a>
                                </div>

                                <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-5">
                                    <x-admin.stat-card label="Total" subtitle="All transaction statuses" :value="$blockchain['total']" />
                                    @foreach ([['Successful', $blockchain['successful'], 'bg-emerald-600'], ['Failed', $blockchain['failed'], 'bg-rose-600'], ['Pending', $blockchain['pending'], 'bg-amber-500'], ['Revoked', $blockchain['revoked'], 'bg-slate-500']] as [$stateLabel, $stateCount, $stateColor])
                                        <x-admin.stat-card :label="$stateLabel" :value="$stateCount" subtitle="Ledger transactions">
                                            <x-slot:icon><span class="{{ $stateColor }} size-2 rounded-full"></span></x-slot:icon>
                                        </x-admin.stat-card>
                                    @endforeach
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
                                    <p class="{{ $muted }}">Document activity and verification outcomes</p>
                                </div>

                                <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-4">
                                    <x-admin.stat-card label="Documents Issued" subtitle="Issued in the selected period" :value="$documents['documents_issued']" />
                                    <x-admin.stat-card label="Verification attempts" subtitle="Logged submissions in the period" :value="$documents['verification_attempts']" />
                                    <x-admin.stat-card label="Valid rate" :subtitle="$documents['verification_attempts'] ? 'Share of attempts that passed' : 'No attempts'" :value="$documents['verification_attempts'] ? number_format($documents['valid_rate'], 1) . '%' : '—'" />
                                    <x-admin.stat-card label="Documents Revoked" subtitle="Revoked in the selected period" :value="$documents['documents_revoked']" />
                                </div>

                                {{-- Scan results: proportional bar + legend. --}}
                                <div class="space-y-2">
                                    @if ($documents['valid'] + $documents['invalid'] + $documents['revoked'] + $documents['other'] > 0)
                                        <div class="flex h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                            role="img" aria-label="Verification attempts by result">
                                            @foreach ([[$documents['valid'], 'bg-emerald-600'], [$documents['invalid'], 'bg-rose-600'], [$documents['revoked'], 'bg-slate-500'], [$documents['other'], 'bg-slate-300 dark:bg-slate-600']] as [$segValue, $segColor])
                                                @if ($segValue > 0)
                                                    <span class="{{ $segColor }}" style="flex: {{ $segValue }} 1 0%"></span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="{{ $muted }}">No data in this period.</p>
                                    @endif
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-300">
                                        @foreach ([['Valid', $documents['valid'], 'bg-emerald-600'], ['Invalid results', $documents['invalid'], 'bg-rose-600'], ['Revoked', $documents['revoked'], 'bg-slate-500'], ['Other', $documents['other'], 'bg-slate-300 dark:bg-slate-600']] as [$segLabel, $segValue, $segColor])
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="{{ $segColor }} size-2 rounded-full"></span>
                                                {{ $segLabel }} · <strong class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $segValue }}</strong>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Latest result per issued request</p>
                                    <div class="mt-2 grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 lg:grid-cols-6">
                                        @foreach (['valid' => 'Valid', 'invalid' => 'Invalid', 'revoked' => 'Revoked', 'pending' => 'Pending', 'unavailable' => 'Unavailable', 'not_found' => 'Not found'] as $resultKey => $resultLabel)
                                            <x-admin.stat-card :label="$resultLabel" :value="$latestResults[$resultKey]" subtitle="Latest logged result" />
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">For documents issued in the selected period, these counts use the most recent logged verification, including later attempts. Requests without a logged result are excluded.</p>
                                </div>
                            </article>
                        </section>

                        {{-- Staff performance (admin only) --}}
                        <section aria-labelledby="staff-performance-heading" class="space-y-3">
                            <h2 id="staff-performance-heading" class="text-lg font-bold text-slate-950 dark:text-white">Staff Performance</h2>
                            <p class="{{ $muted }}">Top 10 staff by approvals and rejections completed in the selected period.</p>

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
                        </section>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
