@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    @php
        $exportQuery = [
            'from' => $filters['from']->toDateString(),
            'to' => $filters['to']->toDateString(),
            'document_type' => $filters['document_type'],
        ];
        $averageHours = $summary['average_processing_hours'];
        $averageLabel = $averageHours === null ? '—' : $averageHours.' hrs';
        $presets = [
            'month' => 'This month',
            'last30' => 'Last 30 days',
        ];
        $cards = [
            ['Total requests', $summary['total']],
            ['Pending / in progress', $summary['pending']],
            ['Approved', $summary['approved']],
            ['Rejected', $summary['rejected']],
            ['Average processing time', $averageLabel],
        ];
        $heading = 'font-semibold text-slate-900 dark:text-slate-100';
        $muted = 'text-sm text-slate-500 dark:text-slate-400';
        $emptyState = 'text-sm text-slate-500 dark:text-slate-400';
        $focus = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#17325c] dark:focus-visible:outline-slate-300';
        $buttonOutline = "rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 {$focus}";
        $label = 'mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200';
    @endphp

    <div class="space-y-6" data-reports-page>
        <x-admin.page-header label="Reports" title="Reports & Analytics" text="" />

        <section class="admin-card space-y-4 p-5" aria-labelledby="report-filters-heading">
            <h2 id="report-filters-heading" class="{{ $heading }}">Filters</h2>

            @if ($errors->any())
                <p role="alert" class="text-sm text-red-700 dark:text-red-300">Please correct the filters.</p>
            @endif

            <form method="GET" action="{{ route('admin.reports.index') }}" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="report-from" class="{{ $label }}">From</label>
                        <input id="report-from" name="from" type="date" value="{{ $filters['from']->toDateString() }}" required class="admin-field">
                        @error('from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="report-to" class="{{ $label }}">To</label>
                        <input id="report-to" name="to" type="date" value="{{ $filters['to']->toDateString() }}" required class="admin-field">
                        @error('to') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="report-document-type" class="{{ $label }}">Document type</label>
                        <select id="report-document-type" name="document_type" class="admin-field">
                            <option value="">All document types</option>
                            @foreach (config('barangay_services.services', []) as $service)
                                <option value="{{ $service['type'] }}" @selected($filters['document_type'] === $service['type'])>{{ $service['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-lg bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#0b1f3a] {{ $focus }}">Apply filters</button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2" role="group" aria-label="Date presets">
                    @foreach ($presets as $key => $text)
                        <button type="button" data-report-preset="{{ $key }}" class="{{ $buttonOutline }}">{{ $text }}</button>
                    @endforeach
                </div>
            </form>
        </section>

        <section class="admin-card space-y-3 p-5" aria-labelledby="report-insights-heading">
            <h2 id="report-insights-heading" class="{{ $heading }}">Insights</h2>
            @forelse ($insights as $insight)
                @php
                    $insightClasses = $insight['severity'] === 'critical'
                        ? 'border-red-200 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200'
                        : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200';
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3 {{ $insightClasses }}">
                    <p class="text-sm">{{ $insight['message'] }}</p>
                    @if ($insight['link'])
                        <a href="{{ $insight['link'] }}" class="text-sm font-semibold underline underline-offset-2 {{ $focus }}">Review</a>
                    @endif
                </div>
            @empty
                <p class="{{ $emptyState }}">No issues detected.</p>
            @endforelse
        </section>

        <section aria-labelledby="report-summary-heading">
            <h2 id="report-summary-heading" class="sr-only">Summary</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($cards as [$cardLabel, $cardValue])
                    <x-admin.stat-card :label="$cardLabel" :value="$cardValue" />
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="admin-card p-5" aria-labelledby="requests-over-time-heading">
                <h2 id="requests-over-time-heading" class="{{ $heading }}">Requests over time</h2>
                @if ($summary['total'] > 0)
                    <div class="mt-4 h-64">
                        <canvas data-report-chart="period"
                            data-labels="{{ json_encode($byPeriod['labels']) }}"
                            data-values="{{ json_encode($byPeriod['values']) }}"
                            aria-label="Requests over time, {{ $byPeriod['granularity'] }}" role="img"></canvas>
                    </div>
                @else
                    <p class="mt-4 {{ $emptyState }}">No requests in this period.</p>
                @endif
            </section>

            <section class="admin-card p-5" aria-labelledby="requests-by-type-heading">
                <h2 id="requests-by-type-heading" class="{{ $heading }}">Requests by document type</h2>
                @if ($summary['total'] > 0)
                    <div class="mt-4 h-64">
                        <canvas data-report-chart="document-type"
                            data-labels="{{ json_encode(array_column($byDocumentType, 'label')) }}"
                            data-values="{{ json_encode(array_column($byDocumentType, 'value')) }}"
                            aria-label="Request counts by document type" role="img"></canvas>
                    </div>
                @else
                    <p class="mt-4 {{ $emptyState }}">No requests in this period.</p>
                @endif
            </section>
        </div>

        <section class="admin-card space-y-4 p-5" aria-labelledby="blockchain-status-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="blockchain-status-heading" class="{{ $heading }}">Blockchain status</h2>
                <a href="{{ route('admin.blockchain-ledger.index') }}" class="text-sm font-semibold text-[#17325c] underline underline-offset-2 dark:text-blue-300 {{ $focus }}">Open ledger</a>
            </div>
            <dl class="grid grid-cols-3 gap-3">
                @foreach ([
                    'Recorded on-chain' => $blockchainActivity['recorded'],
                    'Pending' => $blockchainActivity['pending'],
                    'Failed' => $blockchainActivity['failed'],
                ] as $state => $count)
                    <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800">
                        <dt class="text-sm text-slate-600 dark:text-slate-300">{{ $state }}</dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="flex justify-end" aria-label="Export report">
            <a href="{{ route('admin.reports.export.csv', $exportQuery) }}" class="rounded-lg bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#0b1f3a] {{ $focus }}">Download CSV</a>
        </section>
    </div>
@endsection
