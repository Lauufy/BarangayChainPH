<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

#[Fillable([
    'user_id',
    'user_role',
    'action',
    'subject_type',
    'subject_id',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
    'created_at',
])]
/** Stores append-only audit events. */
class AuditLog extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Audit logs are append-only.'));
        static::deleting(fn (): never => throw new LogicException('Audit logs are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Return the user who caused this audit event, when one was signed in. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
