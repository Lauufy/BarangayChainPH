<?php

namespace App\Models\Concerns;

use App\Observers\AuditableObserver;
use Illuminate\Database\Eloquent\Model;

/** Adds model-event audit logging to an Eloquent model. */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn (Model $model) => app(AuditableObserver::class)->created($model));
        static::updated(fn (Model $model) => app(AuditableObserver::class)->updated($model));
        static::deleted(fn (Model $model) => app(AuditableObserver::class)->deleted($model));
    }
}
