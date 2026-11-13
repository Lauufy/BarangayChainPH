<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InsightService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(
        ReportFilterRequest $request,
        ReportService $reports,
        InsightService $insights
    ): View {
        $filters = $request->filters();
        $user = $request->user();

        if ($user->isStaff()) {
            return view('admin.reports.index', [
                'isStaff' => true,
                'filters' => $filters,
                'staffAccess' => $reports->staffAccess($user),
            ]);
        }

        return view('admin.reports.index', [
            ...$this->reportData($reports, $insights, $filters),
            'filters' => $filters,
            'isStaff' => false,
        ]);
    }

    public function csv(ReportFilterRequest $request, ReportService $reports, InsightService $insights): StreamedResponse
    {
        $filters = $request->filters();
        $data = $this->reportData($reports, $insights, $filters);
        $this->logExport($request->user(), $filters, 'csv');
        $filename = 'barangay-report-'.$filters['from']->format('Y-m-d').'-to-'.$filters['to']->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the report stream.');
            }

            try {
                if (fwrite($stream, "\xEF\xBB\xBF") === false) {
                    throw new RuntimeException('Unable to write the report encoding marker.');
                }

                $writeRow = function (array $cells) use ($stream): void {
                    $safeCells = array_map(fn (mixed $cell) => $this->csvSafeCell($cell), $cells);

                    if (fputcsv($stream, $safeCells, ',', '"', '') === false) {
                        throw new RuntimeException('Unable to write the report stream.');
                    }
                };

                $this->writeCsvSection($writeRow, 'Service Requests', [
                    ['Total requests', $data['summary']['total']],
                    ['Pending', $data['summary']['pending']],
                    ['Processing', $data['summary']['processing']],
                    ['In progress', $data['summary']['in_progress']],
                    ['Approved', $data['summary']['approved']],
                    ['Rejected', $data['summary']['rejected']],
                    ['Cancelled', $data['summary']['cancelled']],
                    ['Approval rate of decisions', $data['summary']['approval_rate'].'%'],
                    ['Rejection rate of decisions', $data['summary']['rejection_rate'].'%'],
                    ['Average processing time (hours)', $data['summary']['average_processing_hours'] ?? 'No data'],
                    ['Previous period total', $data['previous_summary']['total']],
                    ['Previous period in progress', $data['previous_summary']['in_progress']],
                    ['Previous period approved', $data['previous_summary']['approved']],
                ]);
                $writeRow(['Permit type', 'Requests']);
                foreach ($data['by_permit_type'] as $row) {
                    $writeRow([$row['label'], $row['value']]);
                }
                $writeRow(['Period', 'Requests', 'In progress', 'Approved', 'Average processing hours']);
                foreach ($data['trends']['labels'] as $index => $label) {
                    $writeRow([
                        $label,
                        $data['trends']['total'][$index],
                        $data['trends']['in_progress'][$index],
                        $data['trends']['approved'][$index],
                        $data['trends']['average_processing_hours'][$index] ?? 'No data',
                    ]);
                }

                $this->writeCsvSection($writeRow, 'Citizen Verification', [
                    ['Registered citizens', $data['citizens']['total']],
                    ['Verified', $data['citizens']['verified']],
                    ['Pending', $data['citizens']['pending']],
                    ['Rejected', $data['citizens']['rejected']],
                    ['Unverified', $data['citizens']['unverified']],
                    ['Identity approval rate', $data['citizens']['approval_rate'].'%'],
                    ['Identity rejection rate', $data['citizens']['rejection_rate'].'%'],
                    ['Rate period', $data['citizens']['review_period_label']],
                    ['Reviews in rate calculation', $data['citizens']['review_count']],
                ]);

                $this->writeCsvSection($writeRow, 'Staff Performance', [
                    ['Unattributed requests', $data['staff_performance']['unattributed']],
                ]);
                $writeRow(['Staff member', 'Processed', 'Approved', 'Rejected', 'Average processing hours']);
                foreach ($data['staff_performance']['staff'] as $row) {
                    $writeRow([
                        $row['staff_name'],
                        $row['processed'],
                        $row['approved'],
                        $row['rejected'],
                        $row['average_processing_hours'] ?? 'No data',
                    ]);
                }

                $this->writeCsvSection($writeRow, 'Blockchain Transactions', [
                    ['Total transactions', $data['blockchain']['total']],
                    ['Successful', $data['blockchain']['successful']],
                    ['Failed', $data['blockchain']['failed']],
                    ['Pending', $data['blockchain']['pending']],
                    ['Revoked', $data['blockchain']['revoked']],
                ]);
                $writeRow(['Reference number', 'Document type', 'Status', 'Transaction hash', 'Explorer link', 'Date']);
                foreach ($data['blockchain']['recent'] as $transaction) {
                    $writeRow([
                        $transaction['reference_number'],
                        $transaction['document_type'],
                        $transaction['status'],
                        $transaction['transaction_hash'],
                        $transaction['explorer_url'],
                        $transaction['date']?->toDateTimeString(),
                    ]);
                }

                $this->writeCsvSection($writeRow, 'Document Verification', [
                    ['Documents issued', $data['documents']['documents_issued']],
                    ['Verification attempts', $data['documents']['verification_attempts']],
                    ['Valid', $data['documents']['valid']],
                    ['Invalid / Hash Mismatch', $data['documents']['invalid']],
                    ['Revoked', $data['documents']['revoked']],
                    ['Other results', $data['documents']['other']],
                    ['Valid verification rate', $data['documents']['valid_rate'].'%'],
                    ['Documents revoked during period', $data['documents']['documents_revoked']],
                    ['Verification attempts note', 'Verification attempts are counted from the date logging began.'],
                ]);
                $writeRow(['Latest result', 'Requests']);
                foreach ($data['documents']['latest_results'] as $status => $count) {
                    $writeRow([$status, $count]);
                }

                $this->writeCsvSection($writeRow, 'Insights', []);
                foreach ($data['insights'] as $insight) {
                    $writeRow([Str::headline($insight['type']), $insight['severity'], $insight['message']]);
                }

                fclose($stream);
            } catch (\Throwable $exception) {
                fclose($stream);

                throw $exception;
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(ReportFilterRequest $request, ReportService $reports, InsightService $insights): Response
    {
        $filters = $request->filters();
        $data = $this->reportData($reports, $insights, $filters);
        $this->logExport($request->user(), $filters, 'pdf');

        return Pdf::loadView('admin.reports.pdf', [
            ...$data,
            'filters' => $filters,
            'generatedAt' => now(),
            'generatorName' => $request->user()->name,
        ])->download('barangay-report-'.$filters['from']->format('Y-m-d').'-to-'.$filters['to']->format('Y-m-d').'.pdf');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function reportData(ReportService $reports, InsightService $insights, array $filters): array
    {
        $summary = $reports->summary($filters);
        $previousSummary = $reports->summary($reports->previousPeriodFilters($filters));

        return [
            'summary' => $summary,
            'previous_summary' => $previousSummary,
            'comparisons' => $this->comparisons($summary, $previousSummary),
            'trends' => $reports->trends($filters),
            'by_permit_type' => $reports->byPermitType($filters),
            'citizens' => $reports->citizenVerification($filters),
            'staff_performance' => $reports->staffPerformance($filters),
            'blockchain' => $reports->blockchainTransactions($filters),
            'documents' => $reports->documentVerification($filters),
            'insights' => $insights->insights($filters),
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

    private function writeCsvSection(callable $writeRow, string $title, array $rows): void
    {
        $writeRow([]);
        $writeRow([$title]);

        foreach ($rows as $row) {
            $writeRow($row);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function logExport(?User $actor, array $filters, string $format): void
    {
        if ($actor === null) {
            throw new RuntimeException('An authenticated user is required to export reports.');
        }

        app(AuditLogger::class)->log(
            'report_'.$format.'_exported',
            $actor,
            newValues: [
                'filters' => [
                    'from' => $filters['from']->toDateString(),
                    'to' => $filters['to']->toDateString(),
                    'permit_type' => $filters['permit_type'],
                    'grouping' => $filters['grouping'],
                ],
                'format' => $format,
            ],
            actor: $actor
        );
    }

    private function csvSafeCell(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value < 0 ? "'".$value : $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (! is_string($value)) {
            return '';
        }

        $firstMeaningfulCharacter = ltrim($value, " \t\r\n");

        return preg_match('/^[=+\-@]/', $firstMeaningfulCharacter) === 1
            ? "'".$value
            : $value;
    }
}
