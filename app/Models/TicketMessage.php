<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'user_id', 'body', 'is_system', 'read_at', 'attachment_path', 'attachment_name'])]
class TicketMessage extends Model
{
    protected $touches = ['ticket'];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'attachment_name' => 'encrypted',
            'is_system' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function imageContentType(): ?string
    {
        return match (strtolower(pathinfo($this->attachment_path ?? '', PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => null,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
