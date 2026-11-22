<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SyncVersion
{
    public static function bumpAdmin(): void
    {
        Cache::forever('sync:admin', (string) Str::uuid());
    }

    public static function bumpUser(int|string $id): void
    {
        Cache::forever("sync:user:{$id}", (string) Str::uuid());
    }

    public static function current(int|string $userId, string $scope): string
    {
        $user = Cache::get("sync:user:{$userId}", '0');

        return $scope === 'admin'
            ? Cache::get('sync:admin', '0') . '|' . $user
            : $user;
    }
}
