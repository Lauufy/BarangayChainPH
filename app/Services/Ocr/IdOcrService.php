<?php

namespace App\Services\Ocr;

use thiagoalessio\TesseractOCR\TesseractOCR;

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
        $ocr = new TesseractOCR($absolutePath);

        // Set TESSERACT_PATH in .env if the program is not on the PATH
        // (common on Windows), e.g. C:\Program Files\Tesseract-OCR\tesseract.exe
        if ($path = env('TESSERACT_PATH')) {
            $ocr->executable($path);
        }

        return $ocr->lang('eng')->psm(6)->run();
    }
}
