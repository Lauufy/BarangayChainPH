<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PermitStatusCache
{
    public static function keyFor(string $documentUuid): string
    {
        return 'permit-status:'.$documentUuid;
    }

    public static function forget(string $documentUuid): void
    {
        Cache::forget(self::keyFor($documentUuid));
    }
}
