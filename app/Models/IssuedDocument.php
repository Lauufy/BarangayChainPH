<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
/** Stores an issued certificate and its verification metadata. */
class IssuedDocument extends Model
{
    use Auditable;

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

    public function blockchainRecord(): HasOne
    {
        return $this->hasOne(BlockchainRecord::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }
}