<?php

namespace App\Services;

class ReportDataService
{
    public function __construct(
        private ReportService $reports,
        private InsightService $insights,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function forFilters(array $filters): array
    {
        $summary = $this->reports->summary($filters);
        $previousSummary = $this->reports->summary($this->reports->previousPeriodFilters($filters));

        return [
            'summary' => $summary,
            'previous_summary' => $previousSummary,
            'comparisons' => $this->comparisons($summary, $previousSummary),
            'trends' => $this->reports->trends($filters),
            'by_permit_type' => $this->reports->byPermitType($filters),
            'citizens' => $this->reports->citizenVerification($filters),
            'staff_performance' => $this->reports->staffPerformance($filters),
            'blockchain' => $this->reports->blockchainTransactions($filters),
            'documents' => $this->reports->documentVerification($filters),
            'insights' => $this->insights->insights($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, ?float>
     */
    private function comparisons(array $current, array $previous): array
    {
        $changes = [];

        foreach (['total', 'in_progress', 'approved', 'average_processing_hours'] as $key) {
            $now = $current[$key];
            $before = $previous[$key];

            $changes[$key] = $now === null || $before === null
                ? null
                : ($before === 0
                    ? ($now === 0 ? 0.0 : null)
                    : round((($now - $before) / $before) * 100, 1));
        }

        return $changes;
    }
}
