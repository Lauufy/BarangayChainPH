<?php

// Put this file at: app/Services/Ocr/IdFieldExtractor.php

namespace App\Services\Ocr;

use DateTimeImmutable;

/**
 * Compares what the citizen typed against the raw OCR text of their ID.
 *
 * It does NOT try to parse labelled fields, because Philippine IDs (PhilID,
 * UMID, driver's license, voter's ID, etc.) all have different layouts.
 * Instead it asks a simpler question: "does the ID text contain this name /
 * this birthdate?" That works across ID types and tolerates small OCR errors.
 */
class IdFieldExtractor
{
    private const MONTHS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    /** Lowercase, remove accents and punctuation, collapse spaces. */
    public function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, ['ñ' => 'n']);
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * True when every meaningful word of $name (3+ letters) appears in the OCR
     * text, allowing 1-2 character OCR mistakes on longer words.
     */
    public function nameFound(string $name, string $ocrText): bool
    {
        $tokens = array_filter(
            explode(' ', $this->normalize($name)),
            fn ($t) => strlen($t) >= 3
        );
        if (! $tokens) {
            return false;
        }

        $ocrTokens = array_unique(explode(' ', $this->normalize($ocrText)));
        foreach ($tokens as $token) {
            if (! $this->tokenInList($token, $ocrTokens)) {
                return false;
            }
        }

        return true;
    }

    /** True when the entered birthdate appears anywhere in the OCR text. */
    public function birthdateFound(?string $enteredDate, string $ocrText): bool
    {
        if (! $enteredDate) {
            return false;
        }
        try {
            $wanted = (new DateTimeImmutable($enteredDate))->format('Y-m-d');
        } catch (\Throwable $e) {
            return false;
        }

        return in_array($wanted, $this->findDates($ocrText), true);
    }

    /** @return string[] every plausible date found, as Y-m-d */
    public function findDates(string $text): array
    {
        $found = [];
        $currentYear = (int) date('Y');
        $add = function (int $y, int $m, int $d) use (&$found, $currentYear) {
            if ($y >= 1900 && $y <= $currentYear && checkdate($m, $d, $y)) {
                $found[sprintf('%04d-%02d-%02d', $y, $m, $d)] = true;
            }
        };

        // 1990-01-05 or 1990/01/05
        if (preg_match_all('/\b(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})\b/', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $add((int) $x[1], (int) $x[2], (int) $x[3]);
            }
        }

        // 05/01/1990 -> accept both day/month and month/day readings
        if (preg_match_all('/\b(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})\b/', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $add((int) $x[3], (int) $x[2], (int) $x[1]);
                $add((int) $x[3], (int) $x[1], (int) $x[2]);
            }
        }

        // JANUARY 5, 1990
        if (preg_match_all('/\b([A-Za-z]{3,9})\.?\s+(\d{1,2}),?\s+(\d{4})\b/', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $month = self::MONTHS[strtolower(substr($x[1], 0, 3))] ?? null;
                if ($month) {
                    $add((int) $x[3], $month, (int) $x[2]);
                }
            }
        }

        // 5 JANUARY 1990
        if (preg_match_all('/\b(\d{1,2})\s+([A-Za-z]{3,9})\.?,?\s+(\d{4})\b/', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $month = self::MONTHS[strtolower(substr($x[2], 0, 3))] ?? null;
                if ($month) {
                    $add((int) $x[3], $month, (int) $x[1]);
                }
            }
        }

        return array_keys($found);
    }

    private function tokenInList(string $token, array $list): bool
    {
        $len = strlen($token);
        $maxDistance = $len >= 9 ? 2 : ($len >= 5 ? 1 : 0);

        foreach ($list as $candidate) {
            if ($candidate === $token) {
                return true;
            }
            if ($maxDistance > 0
                && abs(strlen($candidate) - $len) <= $maxDistance
                && levenshtein($candidate, $token) <= $maxDistance) {
                return true;
            }
        }

        return false;
    }
}
