@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    @php
        $heading = 'font-semibold text-slate-900 dark:text-slate-100';
        $muted = 'text-sm text-slate-500 dark:text-slate-400';
        $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900';
        $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
        $focus = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:focus-visible:outline-blue-300';
        $metricCard = 'flex min-h-44 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-700';
    @endphp

    <div class="space-y-6" data-reports-page>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <a href="{{ route('admin.dashboard') }}" class="mt-1 text-sm font-medium text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-200 {{ $focus }}">← Back to dashboard</a>
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">Reports</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">Reports &amp; Analytics</h1>
                    <p class="{{ $muted }}">A clear view of service delivery and document verification.</p>
                </div>
            </div>
        </div>

        @if ($isStaff)
            <section aria-label="My staff access metrics" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                @foreach ([
                    ['My workload', $staffAccess['my_workload']],
                    ['Requests awaiting action', $staffAccess['awaiting_action']],
                    ['Identity reviews in queue', $staffAccess['identity_reviews_in_queue']],
                    ['Oldest pending request age', $staffAccess['oldest_pending_age_days'] === null ? '—' : $staffAccess['oldest_pending_age_days'].' days'],
                ] as [$metricLabel, $metricValue])
                    <article class="{{ $metricCard }} xl:col-span-3">
                        <p class="{{ $label }}">{{ $metricLabel }}</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums text-slate-950 dark:text-white">{{ $metricValue }}</p>
                    </article>
                @endforeach
            </section>
        @else
            @php
                $exportQuery = [
                    'from' => $filters['from']->toDateString(),
                    'to' => $filters['to']->toDateString(),
                    'permit_type' => $filters['permit_type'],
                    'grouping' => $filters['grouping'],
                ];
                $averageHours = $summary['average_processing_hours'];
                $averageLabel = $averageHours === null ? '—' : number_format($averageHours, 2).' hrs';
                $comparisonText = fn (?float $change): string => $change === null
                    ? 'No prior data'
                    : ($change === 0.0 ? 'No change vs prior' : number_format($change, 1).'% vs prior');
                $comparisonClass = fn (?float $change): string => $change !== null && $change > 0
                    ? 'text-emerald-700 dark:text-emerald-300'
                    : ($change !== null && $change < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400');
                $metricCards = [
                    ['Total Requests', $summary['total'], 'total', $comparisons['total']],
                    ['In Progress', $summary['in_progress'], 'in_progress', $comparisons['in_progress']],
                    ['Approved', $summary['approved'], 'approved', $comparisons['approved']],
                    ['Average Processing Time', $averageLabel, 'processing_time', $comparisons['average_processing_hours']],
                ];
                $latestResults = $documents['latest_results'];
                $latestResultTotal = array_sum($latestResults);
            @endphp

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="{{ $muted }}">
                    {{ $filters['from']->format('M j, Y') }} – {{ $filters['to']->format('M j, Y') }}
                    · {{ ucfirst($filters['grouping']) }} grouping
                </p>
                <div class="flex items-center gap-2">
                    <details class="relative">
                        <summary class="list-none cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 {{ $focus }}">Filters</summary>
                        <div class="absolute right-0 z-30 mt-2 w-[min(90vw,40rem)] rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                            @if ($errors->any())
                                <p role="alert" class="mb-3 text-sm text-rose-700 dark:text-rose-300">Please correct the filters.</p>
                            @endif
                            <form method="GET" action="{{ route('admin.reports.index') }}" class="space-y-4">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="report-from" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">From</label>
                                        <input id="report-from" name="from" type="date" value="{{ $filters['from']->toDateString() }}" required class="admin-field">
                                        @error('from') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="report-to" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">To</label>
                                        <input id="report-to" name="to" type="date" value="{{ $filters['to']->toDateString() }}" required class="admin-field">
                                        @error('to') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="report-permit-type" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Permit type</label>
                                        <select id="report-permit-type" name="permit_type" class="admin-field">
                                            <option value="">All permit types</option>
                                            @foreach (config('barangay_services.services', []) as $service)
                                                <option value="{{ $service['type'] }}" @selected($filters['permit_type'] === $service['type'])>{{ $service['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="report-grouping" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Grouping</label>
                                        <select id="report-grouping" name="grouping" class="admin-field">
                                            @foreach (['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $title)
                                                <option value="{{ $value }}" @selected($filters['grouping'] === $value)>{{ $title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @error('permit_type') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                @error('grouping') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex flex-wrap gap-2" role="group" aria-label="Date presets">
                                        <button type="button" data-report-preset="month" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 {{ $focus }}">This Month</button>
                                        <button type="button" data-report-preset="last30" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 {{ $focus }}">Last 30 Days</button>
                                    </div>
                                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 {{ $focus }}">Apply filters</button>
                                </div>
                            </form>
                        </div>
                    </details>
                    <details class="relative">
                        <summary class="list-none cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 {{ $focus }}">Export</summary>
                        <div class="absolute right-0 z-30 mt-2 min-w-40 rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            <a href="{{ route('admin.reports.export.csv', $exportQuery) }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">CSV (opens in Excel)</a>
                            <a href="{{ route('admin.reports.export.pdf', $exportQuery) }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">PDF</a>
                        </div>
                    </details>
                </div>
            </div>

            <section aria-labelledby="report-insights-heading" class="{{ $card }} space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="report-insights-heading" class="{{ $heading }}">Insights</h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Up to three actionable signals</span>
                </div>
                @forelse ($insights as $insight)
                    @php
                        $insightClasses = $insight['severity'] === 'critical'
                            ? 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200'
                            : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200';
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3 {{ $insightClasses }}">
                        <p class="text-sm">{{ $insight['message'] }}</p>
                        @if ($insight['link'])
                            <a href="{{ $insight['link'] }}" class="text-sm font-semibold underline underline-offset-2 {{ $focus }}">Review</a>
                        @endif
                    </div>
                @empty
                    <p class="{{ $muted }}">No issues detected.</p>
                @endforelse
            </section>

            <section aria-labelledby="service-requests-heading" class="space-y-4">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">01 · Service delivery</p>
                        <h2 id="service-requests-heading" class="text-xl font-bold text-slate-950 dark:text-white">Service Requests</h2>
                    </div>
                    <a href="{{ route('admin.permits.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300 {{ $focus }}">Permit request list</a>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                    @foreach ($metricCards as [$metricLabel, $metricValue, $chartKey, $change])
                        @php
                            $sparklineValues = match ($chartKey) {
                                'processing_time' => $trends['average_processing_hours'],
                                'in_progress' => $trends['in_progress'],
                                'approved' => $trends['approved'],
                                default => $trends['total'],
                            };
                        @endphp
                        <a href="{{ route('admin.permits.index') }}" class="{{ $metricCard }} group xl:col-span-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="{{ $label }}">{{ $metricLabel }}</p>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium {{ $comparisonClass($change) }} dark:bg-slate-800">{{ $comparisonText($change) }}</span>
                            </div>
                            <div class="mt-2 flex items-end justify-between gap-3">
                                <p class="text-3xl font-bold tabular-nums text-slate-950 dark:text-white">{{ $metricValue }}</p>
                                <div class="h-10 w-24 shrink-0">
                                    <canvas data-report-chart="sparkline"
                                        data-values="{{ json_encode($sparklineValues) }}"
                                        aria-label="{{ $metricLabel }} trend" role="img"></canvas>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                    <article class="{{ $card }} xl:col-span-7">
                        <h3 class="{{ $heading }}">Request status</h3>
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                            @foreach ([
                                ['Pending', $summary['pending'], 'bg-amber-500'],
                                ['Processing', $summary['processing'], 'bg-blue-600'],
                                ['Approved', $summary['approved'], 'bg-emerald-600'],
                                ['Rejected', $summary['rejected'], 'bg-rose-600'],
                                ['Cancelled', $summary['cancelled'], 'bg-slate-500'],
                            ] as [$statusLabel, $statusCount, $statusColor])
                                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                    <div class="flex items-center gap-2">
                                        <span class="size-2 rounded-full {{ $statusColor }}"></span>
                                        <span class="text-xs text-slate-600 dark:text-slate-300">{{ $statusLabel }}</span>
                                    </div>
                                    <p class="mt-2 text-xl font-bold tabular-nums text-slate-950 dark:text-white">{{ $statusCount }}</p>
                                </div>
                            @endforeach
                        </div>
                    </article>
                    <article class="{{ $card }} xl:col-span-5">
                        <h3 class="{{ $heading }}">Approval Performance</h3>
                        <p class="{{ $muted }}">Approved share of completed decisions</p>
                        <div class="relative mx-auto mt-3 h-32 max-w-xs">
                            @if ($summary['decisions'] > 0)
                                <canvas data-report-chart="approval-gauge"
                                    data-values="{{ json_encode([$summary['approval_rate'], 100 - $summary['approval_rate']]) }}"
                                    aria-label="Approval performance: {{ $summary['approval_rate'] }} percent" role="img"></canvas>
                                <p class="absolute inset-x-0 bottom-1 text-center text-2xl font-bold text-slate-950 dark:text-white">{{ number_format($summary['approval_rate'], 1) }}%</p>
                            @else
                                <p class="pt-8 text-center {{ $muted }}">No data in this period.</p>
                            @endif
                        </div>
                        <p class="text-center text-xs text-slate-500 dark:text-slate-400">Based on {{ $summary['decisions'] }} approved or rejected requests</p>
                    </article>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                    <article class="{{ $card }} xl:col-span-7">
                        <h3 class="{{ $heading }}">Application Trends</h3>
                        <p class="{{ $muted }}">{{ ucfirst($trends['granularity']) }} applications</p>
                        @if ($summary['total'] > 0)
                            <div class="mt-4 h-64">
                                <canvas data-report-chart="trend" data-labels="{{ json_encode($trends['labels']) }}" data-values="{{ json_encode($trends['total']) }}"
                                    aria-label="Application trends, {{ $trends['granularity'] }} grouping" role="img"></canvas>
                            </div>
                            <details class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                                <summary class="cursor-pointer">View data table</summary>
                                <div class="mt-2 overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead><tr><th class="py-1">Period</th><th>Requests</th><th>In progress</th><th>Approved</th></tr></thead>
                                        <tbody>
                                            @foreach ($trends['labels'] as $index => $periodLabel)
                                                <tr><td class="py-1">{{ $periodLabel }}</td><td>{{ $trends['total'][$index] }}</td><td>{{ $trends['in_progress'][$index] }}</td><td>{{ $trends['approved'][$index] }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        @else
                            <p class="mt-4 {{ $muted }}">No data in this period.</p>
                        @endif
                    </article>
                    <article class="{{ $card }} xl:col-span-5">
                        <h3 class="{{ $heading }}">Applications by Permit Type</h3>
                        @if ($summary['total'] > 0)
                            <div class="mt-4 h-64">
                                <canvas data-report-chart="permit-type"
                                    data-labels="{{ json_encode(array_column($by_permit_type, 'label')) }}"
                                    data-values="{{ json_encode(array_column($by_permit_type, 'value')) }}"
                                    aria-label="Applications by permit type" role="img"></canvas>
                            </div>
                            <details class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                                <summary class="cursor-pointer">View data table</summary>
                                <table class="mt-2 w-full text-left text-xs">
                                    <thead><tr><th class="py-1">Permit type</th><th>Requests</th></tr></thead>
                                    <tbody>@foreach ($by_permit_type as $typeRow)<tr><td class="py-1">{{ $typeRow['label'] }}</td><td>{{ $typeRow['value'] }}</td></tr>@endforeach</tbody>
                                </table>
                            </details>
                        @else
                            <p class="mt-4 {{ $muted }}">No data in this period.</p>
                        @endif
                    </article>
                </div>
            </section>

            <section aria-labelledby="citizen-verification-heading" class="space-y-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">02 · Identity review</p>
                    <h2 id="citizen-verification-heading" class="text-xl font-bold text-slate-950 dark:text-white">Citizen Verification</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                    <article class="{{ $metricCard }} xl:col-span-3">
                        <p class="{{ $label }}">Total Registered Citizens</p>
                        <p class="mt-3 text-3xl font-bold tabular-nums text-slate-950 dark:text-white">{{ $citizens['total'] }}</p>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">All registered citizen accounts</p>
                    </article>
                    <article class="{{ $card }} xl:col-span-5">
                        <h3 class="{{ $heading }}">Identity Status</h3>
                        @if ($citizens['total'] > 0)
                            <div class="mx-auto mt-2 h-48 max-w-xs">
                                <canvas data-report-chart="citizens" data-labels="{{ json_encode(['Verified', 'Pending', 'Rejected', 'Unverified']) }}"
                                    data-values="{{ json_encode([$citizens['verified'], $citizens['pending'], $citizens['rejected'], $citizens['unverified']]) }}"
                                    aria-label="Identity verification status for registered citizens" role="img"></canvas>
                            </div>
                        @else
                            <p class="mt-4 {{ $muted }}">No data in this period.</p>
                        @endif
                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <span>Verified · {{ $citizens['verified'] }}</span><span>Pending · {{ $citizens['pending'] }}</span>
                            <span>Rejected · {{ $citizens['rejected'] }}</span><span>Unverified · {{ $citizens['unverified'] }}</span>
                        </div>
                    </article>
                    <article class="{{ $card }} space-y-4 xl:col-span-4">
                        <h3 class="{{ $heading }}">Identity Review Rates</h3>
                        <p class="text-3xl font-bold tabular-nums text-slate-950 dark:text-white">{{ number_format($citizens['approval_rate'], 1) }}% <span class="text-sm font-medium text-slate-500 dark:text-slate-400">approval</span></p>
                        <p class="text-2xl font-bold tabular-nums text-slate-950 dark:text-white">{{ number_format($citizens['rejection_rate'], 1) }}% <span class="text-sm font-medium text-slate-500 dark:text-slate-400">rejection</span></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $citizens['review_period_label'] }} · {{ $citizens['review_count'] }} reviewed decisions</p>
                    </article>
                </div>
            </section>

            <section aria-label="Blockchain and document verification" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                <article class="{{ $card }} space-y-4 xl:col-span-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">03 · Ledger</p>
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">Blockchain Transactions</h2>
                        </div>
                        <a href="{{ route('admin.blockchain-ledger.index') }}" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300 {{ $focus }}">Open ledger</a>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach (['total' => 'Total', 'successful' => 'Successful', 'failed' => 'Failed', 'pending' => 'Pending', 'revoked' => 'Revoked'] as $state => $stateLabel)
                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800">
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $stateLabel }}</p>
                                <p class="mt-1 text-xl font-bold tabular-nums text-slate-950 dark:text-white">{{ $blockchain[$state] }}</p>
                            </div>
                        @endforeach
                    </div>
                    @if ($blockchain['total'] > 0)
                        <div class="mx-auto h-52 max-w-xs">
                            <canvas data-report-chart="blockchain" data-labels="{{ json_encode(['Successful', 'Failed', 'Pending', 'Revoked']) }}"
                                data-values="{{ json_encode([$blockchain['successful'], $blockchain['failed'], $blockchain['pending'], $blockchain['revoked']]) }}"
                                aria-label="Blockchain transaction totals by status" role="img"></canvas>
                        </div>
                    @else
                        <p class="{{ $muted }}">No data in this period.</p>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <caption class="pb-2 text-left font-semibold text-slate-700 dark:text-slate-200">Five most recent transactions</caption>
                            <thead class="text-slate-500 dark:text-slate-400"><tr><th class="py-2">Reference</th><th>Type</th><th>Status</th><th>Transaction</th><th>Date</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($blockchain['recent'] as $transaction)
                                    <tr>
                                        <td class="py-2">{{ $transaction['reference_number'] }}</td>
                                        <td>{{ $transaction['document_type'] }}</td>
                                        <td>{{ $transaction['status'] }}</td>
                                        <td>
                                            @if ($transaction['explorer_url'])
                                                <a href="{{ $transaction['explorer_url'] }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline dark:text-blue-300 {{ $focus }}">{{ $transaction['transaction_hash'] ?? 'View transaction' }}</a>
                                            @else
                                                {{ $transaction['transaction_hash'] ?? '—' }}
                                            @endif
                                        </td>
                                        <td>{{ $transaction['date']?->format('M j, Y') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-3 text-slate-500 dark:text-slate-400">No data in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="{{ $card }} space-y-4 xl:col-span-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">04 · QR validation</p>
                        <h2 class="text-lg font-bold text-slate-950 dark:text-white">Document Verification</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="{{ $label }}">Documents Issued</p><p class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">{{ $documents['documents_issued'] }}</p></div>
                        <div><p class="{{ $label }}">QR Verification Scans</p><p class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">{{ $documents['verification_attempts'] }}</p></div>
                        <div><p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Valid · {{ $documents['valid'] }}</p></div>
                        <div><p class="text-xs font-medium text-rose-700 dark:text-rose-300">Invalid / Hash Mismatch · {{ $documents['invalid'] }}</p></div>
                        <div><p class="text-xs font-medium text-slate-700 dark:text-slate-300">Revoked · {{ $documents['revoked'] }}</p></div>
                        <div><p class="text-xs font-medium text-slate-700 dark:text-slate-300">Other results · {{ $documents['other'] }}</p></div>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-950/30">
                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">Valid verification rate · {{ number_format($documents['valid_rate'], 1) }}%</p>
                        <p class="mt-1 text-xs text-blue-800 dark:text-blue-300">{{ $documents['documents_revoked'] }} documents revoked during this period.</p>
                    </div>
                    @if ($documents['verification_attempts'] > 0)
                        <div class="h-28">
                            <canvas data-report-chart="verification-status"
                                data-labels="{{ json_encode(['Valid', 'Invalid / Hash Mismatch', 'Revoked', 'Other']) }}"
                                data-values="{{ json_encode([$documents['valid'], $documents['invalid'], $documents['revoked'], $documents['other']]) }}"
                                aria-label="Verification attempts by result, including invalid or hash mismatch" role="img"></canvas>
                        </div>
                    @else
                        <p class="{{ $muted }}">No data in this period.</p>
                    @endif
                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Latest verification result by issued request</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Status follows the latest logged result for each request; logs do not identify document versions. Invalid / Hash Mismatch refers to issued-document hash validation, not tampered scanned copies.</p>
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">
                            Valid {{ $latestResults['valid'] }} · Invalid {{ $latestResults['invalid'] }} · Revoked {{ $latestResults['revoked'] }} · Pending {{ $latestResults['pending'] }} · Unavailable {{ $latestResults['unavailable'] }} · Not found {{ $latestResults['not_found'] }}
                        </p>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Verification attempts are counted from the date logging began.</p>
                </article>
            </section>

            <section aria-labelledby="staff-performance-heading" class="{{ $card }} space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">05 · Admin only</p>
                        <h2 id="staff-performance-heading" class="text-xl font-bold text-slate-950 dark:text-white">Staff Performance</h2>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $staff_performance['unattributed'] }} unattributed requests in this period</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-2xl text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            <tr><th class="py-3 pr-4">Staff member</th><th class="py-3 pr-4">Processed</th><th class="py-3 pr-4">Approved</th><th class="py-3 pr-4">Rejected</th><th class="py-3">Avg. processing time</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($staff_performance['staff'] as $staff)
                                <tr><td class="py-3 pr-4 font-medium text-slate-900 dark:text-slate-100">{{ $staff['staff_name'] }}</td><td class="py-3 pr-4">{{ $staff['processed'] }}</td><td class="py-3 pr-4">{{ $staff['approved'] }}</td><td class="py-3 pr-4">{{ $staff['rejected'] }}</td><td class="py-3">{{ $staff['average_processing_hours'] === null ? '—' : number_format($staff['average_processing_hours'], 2).' hrs' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-slate-500 dark:text-slate-400">No data in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
