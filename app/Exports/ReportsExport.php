<?php

namespace App\Exports;

use App\Exports\Contracts\AdminReportExport;
use App\Services\ReportDataService;
use App\Services\ReportFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportsExport implements AdminReportExport
{
    public function __construct(
        private ReportFilters $filters,
        private ReportDataService $reportData,
    ) {}

    public function data(Request $request): array
    {
        $filters = $this->filters->validate($request);
        $data = $this->reportData->forFilters($filters);
        $selectedPermit = collect(config('barangay_services.services', []))
            ->firstWhere('type', $filters['permit_type']);

        return [
            'filename' => 'barangay-report-'.$filters['from']->format('Y-m-d').'-to-'.$filters['to']->format('Y-m-d'),
            'filters' => [
                'from' => $filters['from']->toDateString(),
                'to' => $filters['to']->toDateString(),
                'permit_type' => $filters['permit_type'],
                'grouping' => $filters['grouping'],
            ],
            'csv_rows' => $this->csvRows($data),
            'pdf_view' => 'admin.reports.pdf',
            'pdf_data' => [
                ...$data,
                'filters' => $filters,
                'selectedPermit' => $selectedPermit,
                'generatedAt' => now(),
                'generatorName' => $request->user()?->name ?? 'Unknown',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<mixed>>
     */
    private function csvRows(array $data): array
    {
        $rows = [];
        $this->section($rows, 'Service Requests', [
            ['Total requests', $data['summary']['total']],
            ['Pending', $data['summary']['pending']],
            ['Processing', $data['summary']['processing']],
            ['In progress', $data['summary']['in_progress']],
            ['Approved', $data['summary']['approved']],
            ['Rejected', $data['summary']['rejected']],
            ['Cancelled', $data['summary']['cancelled']],
            ['Approval rate of decisions', $data['summary']['decisions'] ? $data['summary']['approval_rate'].'%' : 'No decisions'],
            ['Rejection rate of decisions', $data['summary']['decisions'] ? $data['summary']['rejection_rate'].'%' : 'No decisions'],
            ['Average processing time (hours)', $data['summary']['average_processing_hours'] ?? 'No data'],
            ['Previous period total', $data['previous_summary']['total']],
            ['Previous period in progress', $data['previous_summary']['in_progress']],
            ['Previous period approved', $data['previous_summary']['approved']],
        ]);
        $rows[] = ['Permit type', 'Requests'];

        foreach ($data['by_permit_type'] as $row) {
            $rows[] = [$row['label'], $row['value']];
        }

        $rows[] = ['Period', 'Requests', 'In progress', 'Approved', 'Average processing hours'];

        foreach ($data['trends']['labels'] as $index => $label) {
            $rows[] = [
                $label,
                $data['trends']['total'][$index],
                $data['trends']['in_progress'][$index],
                $data['trends']['approved'][$index],
                $data['trends']['average_processing_hours'][$index] ?? 'No data',
            ];
        }

        $this->section($rows, 'Citizen Verification', [
            ['Registered citizens', $data['citizens']['total']],
            ['Verified', $data['citizens']['verified']],
            ['Pending', $data['citizens']['pending']],
            ['Rejected', $data['citizens']['rejected']],
            ['Unverified', $data['citizens']['unverified']],
            ['Identity approval rate', $data['citizens']['review_count'] ? $data['citizens']['approval_rate'].'%' : 'No decisions'],
            ['Identity rejection rate', $data['citizens']['review_count'] ? $data['citizens']['rejection_rate'].'%' : 'No decisions'],
            ['Rate period', $data['citizens']['review_period_label']],
            ['Reviews in rate calculation', $data['citizens']['review_count']],
        ]);
        $this->section($rows, 'Staff Performance', [
            ['Ranking', 'Top 10 by decisions completed in the period'],
            ['Completed decisions without a recorded processor', $data['staff_performance']['unattributed']],
        ]);
        $rows[] = ['Staff member', 'Processed', 'Approved', 'Rejected', 'Average processing hours'];

        foreach ($data['staff_performance']['staff'] as $row) {
            $rows[] = [
                $row['staff_name'],
                $row['processed'],
                $row['approved'],
                $row['rejected'],
                $row['average_processing_hours'] ?? 'No data',
            ];
        }

        $this->section($rows, 'Blockchain Transactions', [
            ['Total transactions', $data['blockchain']['total']],
            ['Successful', $data['blockchain']['successful']],
            ['Failed', $data['blockchain']['failed']],
            ['Pending', $data['blockchain']['pending']],
            ['Revoked', $data['blockchain']['revoked']],
        ]);
        $rows[] = ['Reference number', 'Document type', 'Status', 'Transaction hash', 'Explorer link', 'Date'];

        foreach ($data['blockchain']['recent'] as $transaction) {
            $rows[] = [
                $transaction['reference_number'],
                $transaction['document_type'],
                $transaction['status'],
                $transaction['transaction_hash'],
                $transaction['explorer_url'],
                $transaction['date']?->toDateTimeString(),
            ];
        }

        $this->section($rows, 'Document Verification', [
            ['Documents issued', $data['documents']['documents_issued']],
            ['Verification attempts', $data['documents']['verification_attempts']],
            ['Valid', $data['documents']['valid']],
            ['Invalid results', $data['documents']['invalid']],
            ['Revoked', $data['documents']['revoked']],
            ['Other results', $data['documents']['other']],
            ['Valid verification rate', $data['documents']['verification_attempts'] ? $data['documents']['valid_rate'].'%' : 'No attempts'],
            ['Documents revoked during period', $data['documents']['documents_revoked']],
            ['Verification attempts note', 'Verification attempts are counted from the date logging began.'],
        ]);
        $rows[] = ['Latest result', 'Requests'];

        foreach ($data['documents']['latest_results'] as $status => $count) {
            $rows[] = [$status, $count];
        }

        $this->section($rows, 'Insights', []);

        foreach ($data['insights'] as $insight) {
            $rows[] = [Str::headline($insight['type']), $insight['severity'], $insight['message']];
        }

        return $rows;
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @param  list<list<mixed>>  $data
     */
    private function section(array &$rows, string $title, array $data): void
    {
        $rows[] = [];
        $rows[] = [$title];

        foreach ($data as $row) {
            $rows[] = $row;
        }
    }
}
