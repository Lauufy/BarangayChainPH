<?php

namespace App\Services\Ocr;

use Symfony\Component\Process\Process;

class IdOcrService
{
    /**
     * Cheap checks before OCR. Returns a list of problem codes (empty = fine).
     */
    public function checkQuality(string $absolutePath): array
    {
        $info = @getimagesize($absolutePath);
        if (! $info) {
            return ['unreadable_image'];
        }

        [$width, $height] = $info;
        $problems = [];

        // A phone photo of an ID should be well above this.
        if (min($width, $height) < 600) {
            $problems[] = 'low_resolution';
        }

        return $problems;
    }

    /** Run Tesseract and return the raw text found on the image. */
    public function read(string $absolutePath): string
    {
        $process = new Process([
            config('permit-verification.tesseract'),
            $absolutePath,
            'stdout',
            '-l',
            'eng',
            '--psm',
            '6',
        ], timeout: 110);
        $process->mustRun();

        return trim($process->getOutput());
    }
}
