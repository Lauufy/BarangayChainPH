<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A citizen's request about their personal data.
class DataSubjectRequest extends Model
{
    protected $fillable = [
        'user_id', 'type', 'status', 'details', 'due_at',
        'handled_by', 'handled_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}