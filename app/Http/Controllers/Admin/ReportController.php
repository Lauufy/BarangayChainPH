<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InsightService;
use App\Services\ReportService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(
        ReportFilterRequest $request,
        ReportService $reports,
        InsightService $insights
    ): View {
        $filters = $request->filters();

        return view('admin.reports.index', [
            ...$this->reportData($reports, $filters),
            'filters' => $filters,
            'insights' => $insights->insights($filters),
        ]);
    }

    public function csv(ReportFilterRequest $request, ReportService $reports): StreamedResponse
    {
        $filters = $request->filters();
        $this->logExport($request->user(), $filters);
        $filename = 'barangay-report-'.$filters['from']->format('Y-m-d').'-to-'.$filters['to']->format('Y-m-d').'.csv';
        $data = $this->reportData($reports, $filters);

        return response()->streamDownload(function () use ($data): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the report stream.');
            }

            fwrite($stream, "\xEF\xBB\xBF");

            $writeRow = function (array $cells) use ($stream): void {
                $safeCells = array_map(fn (mixed $cell) => $this->csvSafeCell($cell), $cells);
                fputcsv($stream, $safeCells, ',', '"', '');
            };

            $writeRow(['Summary']);
            foreach ($data['summary'] as $metric => $value) {
                $writeRow([Str::headline($metric), $value]);
            }

            $writeRow([]);
            $writeRow(['Requests over time']);
            $writeRow(['Period', 'Requests']);
            foreach ($data['byPeriod']['labels'] as $index => $label) {
                $writeRow([$label, $data['byPeriod']['values'][$index]]);
            }

            $writeRow([]);
            $writeRow(['Requests by document type']);
            $writeRow(['Document type', 'Requests']);
            foreach ($data['byDocumentType'] as $row) {
                $writeRow([$row['label'], $row['value']]);
            }

            $writeRow([]);
            $writeRow(['Blockchain status']);
            foreach ([
                'recorded' => 'Recorded on-chain',
                'pending' => 'Pending',
                'failed' => 'Failed',
            ] as $state => $label) {
                $writeRow([$label, $data['blockchainActivity'][$state]]);
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function reportData(ReportService $reports, array $filters): array
    {
        return [
            'summary' => $reports->summary($filters),
            'byDocumentType' => $reports->byDocumentType($filters),
            'byPeriod' => $reports->byPeriod($filters),
            'blockchainActivity' => $reports->blockchainActivity($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function logExport(?User $actor, array $filters): void
    {
        if ($actor === null) {
            throw new RuntimeException('An authenticated user is required to export reports.');
        }

        app(AuditLogger::class)->log(
            'report_csv_exported',
            $actor,
            newValues: [
                'filters' => [
                    'from' => $filters['from']->toDateString(),
                    'to' => $filters['to']->toDateString(),
                    'document_type' => $filters['document_type'],
                ],
            ],
            actor: $actor
        );
    }

    private function csvSafeCell(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value < 0 ? "'".$value : $value;
        }

        if (! is_string($value)) {
            return $value ?? '';
        }

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
