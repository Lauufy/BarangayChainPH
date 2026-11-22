<?php

namespace App\Support;

class Phone
{
    /**
     * Turns 9171234567, 09171234567 or +639171234567 into +639171234567.
     * Returns null when it is not a valid Philippine mobile number.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $input);

        $digits = match (true) {
            str_starts_with($digits, '63') => $digits,
            str_starts_with($digits, '0') => '63'.substr($digits, 1),
            default => '63'.$digits,
        };

        return preg_match('/^639\d{9}$/', $digits) ? '+'.$digits : null;
    }
}
