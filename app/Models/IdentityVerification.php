<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'status',
    'document_type',
    'document_path',
    'document_back_key',
    'id_number_hash',
    'selfie_path',
    'selfie_source',
    'consented_at',
    'submitted_at',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'ocr_status',
    'ocr_flags',
    'ocr_processed_at',
])]

// Never expose private storage keys in JSON or arrays.
#[Hidden([
    'document_path',
    'document_back_key',
    'selfie_path',
    'id_number_hash',
])]
class IdentityVerification extends Model
{
    use Auditable, HasFactory;

    // The possible statuses (Unverified = no row yet)
    public const DRAFT = 'draft';

    public const PENDING = 'pending';

    public const VERIFIED = 'verified';

    public const REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'ocr_flags' => 'array',
            'ocr_processed_at' => 'datetime',
        ];
    }

    // The citizen who submitted this application
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // The staff or admin who reviewed it
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isVerified(): bool
    {
        return $this->status === self::VERIFIED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::REJECTED;
    }
}
