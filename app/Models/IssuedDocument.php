<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_request_id',
    'document_uuid',
    'document_number',
    'version',
    'disk',
    'file_path',
    'file_hash',
    'issued_at',
    'qr_code_generated_at',
    'revoked_at',
])]
class IssuedDocument extends Model
{
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'issued_at' => 'datetime',
            'qr_code_generated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }
}
