<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'status',
    'document_type',
    'document_path',
    'document_original_name',
    'id_number_hash',
    'selfie_path',
    'selfie_source',
    'consented_at',
    'submitted_at',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
])]

// Never expose the private file path in JSON or arrays.
#[Hidden([
    'document_path',
    'selfie_path',
    'id_number_hash',
])]

/**
 * @property int $id
 * @property string $status
 * @property string|null $rejection_reason
 */

class IdentityVerification extends Model
{
    // The possible statuses (Unverified = no row yet)
    public const PENDING = 'pending';
    public const VERIFIED = 'verified';
    public const REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    // Full path of the uploaded ID (kept in the private storage folder)
    public function filePath(): string
    {
        return Storage::disk('local')->path($this->document_path);
    }

    public function selfiePath(): ?string
    {
        return $this->selfie_path
            ? Storage::disk('local')->path($this->selfie_path)
            : null;
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
