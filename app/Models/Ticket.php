<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'service_request_id', 'status'])]
class Ticket extends Model
{
    public const OPEN = 'open';
    public const CLOSED = 'closed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }

    // Post an automatic message; close the ticket when the request is finished
    public function postSystemMessage(string $body, bool $close = false): void
    {
        $this->messages()->create(['body' => $body, 'is_system' => true]);

        if ($close) {
            $this->update(['status' => self::CLOSED]);
        }
    }
}
