<?php

namespace App\Services;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use RuntimeException;
use Symfony\Component\Process\Process;

class PermitFields
{
    public const LABELS = [
        'number' => 'Permit number',
        'holder' => 'Permit holder',
        'type' => 'Permit type',
        'issued' => 'Issue date',
        'expires' => 'Expiry date',
        'issuer' => 'Issuing office',
        'version' => 'Permit version',
    ];

    public static function snapshot(IssuedDocument $document, ServiceRequest $application): array
    {
        return array_map(
            fn (string $value): string => self::normalize($value),
            [
                'number' => $document->document_number,
                'holder' => $application->applicant_name,
                'type' => $application->serviceTitle(),
                'issued' => $document->issued_at->toDateString(),
                'expires' => $document->expires_at?->toDateString() ?? 'None',
                'issuer' => 'Barangay San Luis',
                'version' => (string) $document->version,
            ],
        );
    }

    public function differences(string $path, string $mime, array $expected): array
    {
        if ($mime !== 'application/pdf') {
            $dimensions = @getimagesize($path);
            if (! $dimensions || $dimensions[0] * $dimensions[1] > 25_000_000) {
                throw new RuntimeException('Permit image cannot be processed.');
            }
        }

        $command = $mime === 'application/pdf'
            ? [config('permit-verification.pdftotext'), '-f', '1', '-l', '1', '-enc', 'UTF-8', $path, '-']
            : [config('permit-verification.tesseract'), $path, 'stdout', '-l', 'eng', '--psm', '6'];

        $process = new Process($command, timeout: 8);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Permit field extraction failed.');
        }

        $text = $process->getOutput();
        $legacyPatterns = [
            'number' => '/Document Number\s+(PERMIT-[\w-]+)/iu',
            'holder' => '/This is to officially certify that\s+([^,]+),/iu',
            'issued' => '/Date Issued\s+([a-z]+\s+\d{1,2},\s+\d{4})/iu',
            'expires' => '/This certification is valid until\s+([a-z]+\s+\d{1,2},\s+\d{4})/iu',
        ];
        $differences = [];
        $missing = [];
        $labeledFields = 0;
        foreach (self::LABELS as $key => $label) {
            $value = null;
            if (preg_match('/^\h*'.preg_quote($label, '/').'\h*:\h*([^\r\n]*)/imu', $text, $match)) {
                $labeledFields++;
                $value = self::normalize($match[1]);
            } elseif (isset($legacyPatterns[$key]) && preg_match($legacyPatterns[$key], $text, $match)) {
                $value = self::normalize($match[1]);
                if (in_array($key, ['issued', 'expires'], true) && ($timestamp = strtotime($value)) !== false) {
                    $value = date('Y-m-d', $timestamp);
                }
            }
            if ($value === null || $value === '') {
                $missing[] = $label.' (missing or unreadable)';

                continue;
            }

            if (mb_strtolower($value) !== mb_strtolower($expected[$key])) {
                $differences[] = $label;
            }
        }

        return $labeledFields >= 3 ? [...$differences, ...$missing] : $differences;
    }

    public static function commitment(array $fields): string
    {
        ksort($fields);

        return hash('sha256', json_encode($fields, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private static function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
