<?php

namespace App\Services;

use App\Exports\Contracts\AdminExport;
use App\Exports\Contracts\AdminReportExport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnexpectedValueException;

class ExportService
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function download(
        AdminExport|AdminReportExport $export,
        string $module,
        string $format,
        Request $request,
        User $actor,
        array $filters,
        string $auditAction
    ): Response {
        if ($export instanceof AdminReportExport) {
            $data = $export->data($request);
            $this->logExport($actor, $module, $format, $data['filters'], $auditAction);
            $filename = $this->filename($data['filename'], $format);

            if ($format === 'csv') {
                return $this->csvResponse($filename, function ($stream) use ($data): void {
                    foreach ($data['csv_rows'] as $row) {
                        $this->writeCsvRow($stream, $row);
                    }
                });
            }

            return Pdf::loadView($data['pdf_view'], $data['pdf_data'])
                ->download($filename);
        }

        $query = $export->query($request);
        $this->logExport($actor, $module, $format, $filters, $auditAction);
        $filename = $this->filename($export->filename(), $format);

        if ($format === 'csv') {
            return $this->csvResponse($filename, function ($stream) use ($query, $export): void {
                $this->writeCsvRow($stream, $export->headings());

                $query->chunk(250, function ($models) use ($stream, $export): void {
                    foreach ($models as $model) {
                        $this->writeCsvRow($stream, $export->row($model));
                    }
                });
            });
        }

        return Pdf::loadView('exports.admin-table-pdf', [
            'title' => Str::headline($module),
            'headings' => $export->headings(),
            'rows' => $query->get()->map(fn ($model): array => $export->row($model)),
            'generatedAt' => now(),
        ])->download($filename);
    }

    private function csvResponse(string $filename, callable $write): StreamedResponse
    {
        return response()->streamDownload(function () use ($write): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the export stream.');
            }

            try {
                if (fwrite($stream, "\xEF\xBB\xBF") === false) {
                    throw new RuntimeException('Unable to write the export encoding marker.');
                }

                $write($stream);
            } finally {
                fclose($stream);
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  resource  $stream
     * @param  list<mixed>  $cells
     */
    private function writeCsvRow($stream, array $cells): void
    {
        $safeCells = array_map(fn (mixed $cell): string => $this->safeCell($cell), $cells);

        if (fputcsv($stream, $safeCells, ',', '"', '') === false) {
            throw new RuntimeException('Unable to write the export stream.');
        }
    }

    private function safeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (! is_scalar($value)) {
            throw new UnexpectedValueException('Export rows may contain only scalar values or null.');
        }

        $cell = (string) $value;

        return preg_match('/^[\t\r\n ]*[=+\-@]/', $cell) === 1 ? "'".$cell : $cell;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function logExport(User $actor, string $module, string $format, array $filters, string $action): void
    {
        $newValues = [
            'module' => $module,
            'format' => $format,
            'filters' => $filters,
        ];

        $this->auditLogger->log(
            $action,
            $actor,
            newValues: $newValues,
            actor: $actor
        );
    }

    private function filename(string $name, string $format): string
    {
        $safeName = Str::slug(pathinfo($name, PATHINFO_FILENAME));

        if ($safeName === '') {
            throw new UnexpectedValueException('Export filenames must contain a safe name.');
        }

        return $safeName.'-'.now()->format('Y-m-d-His').'.'.$format;
    }
}
