@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    <div class="space-y-4" data-reports-page>
        {{-- Page header with export actions (admins only). --}}
        <x-admin.page-header label="Reports" title="Reports & Analytics"
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
            <section aria-label="My staff access metrics" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([['My workload', $staffAccess['my_workload']], ['Requests awaiting action', $staffAccess['awaiting_action']], ['Identity reviews in queue', $staffAccess['identity_reviews_in_queue']], ['Oldest pending request age', $staffAccess['oldest_pending_age_days'] === null ? '—' : $staffAccess['oldest_pending_age_days'] . ' days']] as [$metricLabel, $metricValue])
                    <article class="{{ $metricCard }}">
                        <p class="{{ $label }}">{{ $metricLabel }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-950 dark:text-white">{{ $metricValue }}
                        </p>
                    </article>
                @endforeach
            </section>
        @else
            @php
                $reportTabs = [
                    'service' => 'Service Requests',
                    'citizens' => 'Citizens',
                    'verification' => 'Verification',
                ];
                $activeTab = array_key_exists(request('report_tab'), $reportTabs) ? request('report_tab') : 'service';

                // Shared styles.
                $panel =
                    'space-y-3 rounded-2xl ';
                $primaryBtn =
                    $focus .
                    ' inline-flex h-10 items-center whitespace-nowrap rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700';
                $statValue = 'font-bold tabular-nums text-slate-950 dark:text-white';

                // Segments for the stacked progress bars: [label, value, color class].
                $statusSegments = [
                    ['Pending', $summary['pending'], 'bg-amber-500'],
                    ['Processing', $summary['processing'], 'bg-blue-600'],
                    ['Approved', $summary['approved'], 'bg-emerald-600'],
                    ['Rejected', $summary['rejected'], 'bg-rose-600'],
                    ['Cancelled', $summary['cancelled'], 'bg-slate-500'],
                ];
                $ledgerSegments = [
                    ['Successful', $blockchain['successful'], 'bg-emerald-600'],
                    ['Failed', $blockchain['failed'], 'bg-rose-600'],
                    ['Pending', $blockchain['pending'], 'bg-amber-500'],
                    ['Revoked', $blockchain['revoked'], 'bg-slate-500'],
                ];
                $scanSegments = [
                    ['Valid', $documents['valid'], 'bg-emerald-600'],
                    ['Invalid / Hash Mismatch', $documents['invalid'], 'bg-rose-600'],
                    ['Revoked', $documents['revoked'], 'bg-slate-500'],
                    ['Other', $documents['other'], 'bg-slate-300 dark:bg-slate-600'],
                ];
            @endphp

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

            <div class="bg-white p-4 shadow-sm dark:bg-slate-900 rounded-lg">
                {{-- Tabs, filters and permit list shortcut (filters carry over between tabs). --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <x-tables.toolbar key="report" :tabs="$reportTabs" class="pb-0! sm:pb-0!" />

                    <div class="flex items-center gap-2">
                        <details class="relative">
                            <summary
                                class="{{ $focus }} inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M22 3H2l8 9.46V19l4 2v-8.54z" />
                                </svg>
                                Filters
                            </summary>
                            <div
                                class="absolute right-0 z-30 mt-2 w-[min(90vw,40rem)] rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                @if ($errors->any())
                                    <p role="alert" class="mb-3 text-sm text-rose-700 dark:text-rose-300">Please correct
                                        the
                                        filters.</p>
                                @endif
                                <form method="GET" action="{{ route('admin.reports.index') }}" class="space-y-4">
                                    <input type="hidden" name="report_tab" value="{{ $activeTab }}">
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
                                                class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Permit
                                                type</label>
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
                                                class="{{ $focus }} rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">This
                                                Month</button>
                                            <button type="button" data-report-preset="last30"
                                                class="{{ $focus }} rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">Last
                                                30 Days</button>
                                        </div>
                                        <button type="submit" class="{{ $primaryBtn }}">Apply filters</button>
                                    </div>
                                </form>
                            </div>
                        </details>

                        @if ($activeTab === 'service')
                            <a href="{{ route('admin.permits.index') }}" class="{{ $primaryBtn }}">Permit request list</a>
                        @endif
                    </div>
                </div>

                {{-- ───────── Service Requests ───────── --}}
                @if ($activeTab === 'service')
                    <div class="{{ $panel }}">
                        <h2 class="sr-only">Service Requests</h2>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($metricCards as [$metricLabel, $metricValue, $comparisonLabel, $comparisonTone])
                                <a href="{{ route('admin.permits.index') }}" class="{{ $metricCard }} group">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="{{ $label }}">{{ $metricLabel }}</p>
                                        <span
                                            class="{{ $comparisonTone }} rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium dark:bg-slate-800">{{ $comparisonLabel }}</span>
                                    </div>
                                    <p class="{{ $statValue }} mt-2 text-3xl">{{ $metricValue }}</p>
                                </a>
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
                @if ($activeTab === 'citizens')
                    <div class="{{ $panel }}">
                        <h2 class="sr-only">Citizen Verification</h2>

                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <article class="{{ $card }} lg:col-span-7">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <h3 class="{{ $heading }}">Identity Status</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-300">
                                        <span class="{{ $statValue }} text-xl">{{ $citizens['total'] }}</span>
                                        registered
                                        citizens
                                    </p>
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
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span
                                                class="text-sm text-slate-600 dark:text-slate-300">{{ $rateLabel }}</span>
                                            <span
                                                class="{{ $statValue }} text-2xl">{{ number_format($rate, 1) }}%</span>
                                        </div>
                                        <div
                                            class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
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
                                <p class="text-sm text-slate-600 dark:text-slate-300">
                                    <span
                                        class="{{ $statValue }}">{{ $summary['decisions'] > 0 ? number_format($summary['approval_rate'], 1) . '%' : '—' }}</span>
                                    approved · based on {{ $summary['decisions'] }} approved or rejected requests
                                </p>
                            </div>
                            @php $statusTotal = array_sum(array_column($statusSegments, 1)); @endphp
                            @if ($statusTotal > 0)
                                <div class="flex h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                    role="img" aria-label="Share of requests by status">
                                    @foreach ($statusSegments as [$segLabel, $segValue, $segColor])
                                        @if ($segValue > 0)
                                            <span class="{{ $segColor }}"
                                                style="width: {{ round(($segValue / $statusTotal) * 100, 2) }}%"></span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                                @foreach ($statusSegments as [$statusLabel, $statusCount, $statusColor])
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <div class="flex items-center gap-2">
                                            <span class="{{ $statusColor }} size-2 rounded-full"></span>
                                            <span
                                                class="text-xs text-slate-600 dark:text-slate-300">{{ $statusLabel }}</span>
                                        </div>
                                        <p class="{{ $statValue }} mt-1.5 text-xl">{{ $statusCount }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    </div>
                @endif

                {{-- ───────── Verification ───────── --}}
                @if ($activeTab === 'verification')
                    <div class="{{ $panel }}">
                        {{-- Blockchain --}}
                        <section aria-labelledby="ledger-heading">
                            <article class="{{ $card }} space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h2 id="ledger-heading" class="{{ $heading }}">Blockchain Transactions</h2>
                                        <p class="{{ $muted }}">Document records anchored on the ledger</p>
                                    </div>
                                    <a href="{{ route('admin.blockchain-ledger.index') }}"
                                        class="{{ $primaryBtn }}">Open ledger</a>
                                </div>

                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Total</p>
                                        <p class="{{ $statValue }} mt-1 text-xl">{{ $blockchain['total'] }}</p>
                                    </div>
                                    @foreach ($ledgerSegments as [$stateLabel, $stateCount, $stateColor])
                                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span class="{{ $stateColor }} size-2 rounded-full"></span>
                                                <span
                                                    class="text-xs text-slate-500 dark:text-slate-400">{{ $stateLabel }}</span>
                                            </div>
                                            <p class="{{ $statValue }} mt-1 text-xl">{{ $stateCount }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <x-tables.card title="Five most recent transactions" :searchable="false" :per-page-options="[]">
                                    <x-tables.table :heads="['Reference', 'Type', 'Status', 'Transaction', 'Date']" :empty="empty($blockchain['recent'])"
                                        message="No transactions in this period.">
                                        @foreach ($blockchain['recent'] as $transaction)
                                            <tr>
                                                <td>{{ $transaction['reference_number'] }}</td>
                                                <td>{{ $transaction['document_type'] }}</td>
                                                <td>
                                                    <x-tables.badge :variant="match ($transaction['status']) {
                                                        'Successful' => 'success',
                                                        'Failed' => 'danger',
                                                        'Pending' => 'warning',
                                                        'Revoked' => 'neutral',
                                                        default => 'info',
                                                    }">
                                                        {{ $transaction['status'] }}
                                                    </x-tables.badge>
                                                </td>
                                                <td>
                                                    @if ($transaction['explorer_url'])
                                                        <a href="{{ $transaction['explorer_url'] }}" target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="{{ $focus }} text-blue-700 underline dark:text-blue-300">{{ $transaction['transaction_hash'] ?? 'View transaction' }}</a>
                                                    @else
                                                        {{ $transaction['transaction_hash'] ?? '—' }}
                                                    @endif
                                                </td>
                                                <td>{{ $transaction['date']?->format('M j, Y') ?? '—' }}</td>
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
                                    <p class="{{ $muted }}">QR scans of issued documents</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <p class="{{ $label }}">Documents Issued</p>
                                        <p class="{{ $statValue }} mt-1 text-2xl">
                                            {{ $documents['documents_issued'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <p class="{{ $label }}">QR Verification Scans</p>
                                        <p class="{{ $statValue }} mt-1 text-2xl">
                                            {{ $documents['verification_attempts'] }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-blue-50 p-3 dark:bg-blue-950/30">
                                        <p class="{{ $label }}">Valid rate</p>
                                        <p class="{{ $statValue }} mt-1 text-2xl">
                                            {{ number_format($documents['valid_rate'], 1) }}%</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <p class="{{ $label }}">Documents Revoked</p>
                                        <p class="{{ $statValue }} mt-1 text-2xl">
                                            {{ $documents['documents_revoked'] }}</p>
                                    </div>
                                </div>

                                {{-- Scan results: proportional bar + legend. --}}
                                @php $scanTotal = array_sum(array_column($scanSegments, 1)); @endphp
                                <div class="space-y-2">
                                    @if ($scanTotal > 0)
                                        <div class="flex h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                            role="img" aria-label="Verification scans by result">
                                            @foreach ($scanSegments as [$segLabel, $segValue, $segColor])
                                                @if ($segValue > 0)
                                                    <span class="{{ $segColor }}"
                                                        style="width: {{ round(($segValue / $scanTotal) * 100, 2) }}%"></span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="{{ $muted }}">No data in this period.</p>
                                    @endif
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600 dark:text-slate-300">
                                        @foreach ($scanSegments as [$segLabel, $segValue, $segColor])
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="{{ $segColor }} size-2 rounded-full"></span>
                                                {{ $segLabel }} · <strong
                                                    class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $segValue }}</strong>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Latest result per
                                        issued request</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                                        @foreach (['valid' => 'Valid', 'invalid' => 'Invalid', 'revoked' => 'Revoked', 'pending' => 'Pending', 'unavailable' => 'Unavailable', 'not_found' => 'Not found'] as $resultKey => $resultLabel)
                                            <span
                                                class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $resultLabel }}
                                                <strong
                                                    class="ml-0.5 font-semibold tabular-nums">{{ $latestResults[$resultKey] }}</strong></span>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Based on the latest logged result for each request. Invalid / Hash Mismatch refers to issued-document hash validation, not tampered scanned copies. Scans are counted from the date logging began.</p>
                                </div>
                            </article>
                        </section>

                        {{-- Staff performance (admin only) --}}
                        <section aria-labelledby="staff-performance-heading" class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <h2 id="staff-performance-heading"
                                    class="text-lg font-bold text-slate-950 dark:text-white">
                                    Staff Performance</h2>
                                <x-tables.toolbar key="report-staff-performance" :search="true"
                                    search-placeholder="Search staff..." class="pb-0! sm:pb-0!" />
                            </div>
                            <x-tables.card :searchable="false" :per-page-options="[]">
                                <x-tables.table :heads="[
                                    'Staff member',
                                    'Processed',
                                    'Approved',
                                    'Rejected',
                                    'Avg. processing time',
                                ]" :empty="empty($staff_performance['staff'])"
                                    message="No staff data in this period.">
                                    @foreach ($staff_performance['staff'] as $staff)
                                        <tr>
                                            <td><x-tables.user-cell :name="$staff['staff_name']" /></td>
                                            <td>{{ $staff['processed'] }}</td>
                                            <td>{{ $staff['approved'] }}</td>
                                            <td>{{ $staff['rejected'] }}</td>
                                            <td>{{ $staff['average_processing_hours'] === null ? '—' : number_format($staff['average_processing_hours'], 2) . ' hrs' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </x-tables.table>
                            </x-tables.card>
                        </section>
                    </div>
                @endif
        @endif
    </div>
    </div>
@endsection
