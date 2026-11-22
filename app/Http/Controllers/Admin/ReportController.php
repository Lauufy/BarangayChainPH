<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Services\ReportDataService;
use App\Services\ReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(
        ReportFilterRequest $request,
        ReportDataService $reportData,
        ReportService $reports
    ): View {
        $filters = $request->filters();
        $user = $request->user();

        if ($user->isStaff()) {
            return view('admin.reports.index', [
                ...$this->reportPageStyles(),
                'isStaff' => true,
                'filters' => $filters,
                'staffAccess' => $reports->staffAccess($user),
            ]);
        }

        $data = $reportData->forFilters($filters);

        return view('admin.reports.index', [
            ...$this->reportPageStyles(),
            ...$this->reportPagePresentation($data, $filters),
            ...$data,
            'filters' => $filters,
            'isStaff' => false,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function reportPageStyles(): array
    {
        return [
            'heading' => 'font-semibold text-slate-900 dark:text-slate-100',
            'muted' => 'text-sm text-slate-500 dark:text-slate-400',
            'card' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900',
            'label' => 'block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400',
            'focus' => 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:focus-visible:outline-blue-300',
            'metricCard' => 'flex min-h-44 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-700',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function reportPagePresentation(array $data, array $filters): array
    {
        $summary = $data['summary'];
        $comparisons = $data['comparisons'];
        $trends = $data['trends'];
        $averageHours = $summary['average_processing_hours'];
        $averageLabel = $averageHours === null ? '—' : number_format($averageHours, 2).' hrs';

        $metricCards = [
            ['Total Requests', $summary['total'], 'total', $comparisons['total']],
            ['In Progress', $summary['in_progress'], 'in_progress', $comparisons['in_progress']],
            ['Approved', $summary['approved'], 'approved', $comparisons['approved']],
            ['Average Processing Time', $averageLabel, 'processing_time', $comparisons['average_processing_hours']],
        ];

        $metricCards = array_map(function (array $metric) use ($trends): array {
            [$label, $value, $chartKey, $change] = $metric;
            $sparklineValues = match ($chartKey) {
                'processing_time' => $trends['average_processing_hours'],
                'in_progress' => $trends['in_progress'],
                'approved' => $trends['approved'],
                default => $trends['total'],
            };

            return [
                $label,
                $value,
                $this->comparisonText($change),
                $this->comparisonClass($change),
                $sparklineValues,
            ];
        }, $metricCards);

        return [
            'exportQuery' => [
                'from' => $filters['from']->toDateString(),
                'to' => $filters['to']->toDateString(),
                'permit_type' => $filters['permit_type'],
                'grouping' => $filters['grouping'],
            ],
            'metricCards' => $metricCards,
            'latestResults' => $data['documents']['latest_results'],
        ];
    }

    private function comparisonText(?float $change): string
    {
        return $change === null
            ? 'No prior data'
            : ($change === 0.0 ? 'No change vs prior' : number_format($change, 1).'% vs prior');
    }

    private function comparisonClass(?float $change): string
    {
        return $change !== null && $change > 0
            ? 'text-emerald-700 dark:text-emerald-300'
            : ($change !== null && $change < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400');
    }
}
