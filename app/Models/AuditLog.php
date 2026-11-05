<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'old_values',
    'new_values',
    'created_at',
])]
class AuditLog extends Model
{
    public $timestamps = false;

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
}
