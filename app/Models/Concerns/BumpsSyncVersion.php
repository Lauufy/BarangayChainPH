<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\SyncVersion;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait BumpsSyncVersion
{
    protected static function bootBumpsSyncVersion(): void
    {
        $bump = function ($model) {
            SyncVersion::bumpAdmin();

            // Change "user_id" if your citizen-owned tables use another column
            $owner = $model instanceof User ? $model->getKey() : ($model->user_id ?? null);

            if ($owner) {
                SyncVersion::bumpUser($owner);
            }
        };

        static::saved($bump);
        static::deleted($bump);
    }
}