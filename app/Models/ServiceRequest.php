<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference_number
 * @property string $status
 * @property string|null $rejection_reason
 */
#[Fillable([
    'user_id',
    'reference_number',
    'service_type',
    'applicant_name',
    'address',
    'age',
    'purpose',
    'status',
    'cancellation_reason',
    'cancelled_at',
    'rejection_reason',
    'permit_number',
    'print_count',
    'print_limit',
])]
class ServiceRequest extends Model
{
    public const CERTIFICATE_OF_INDIGENCY = 'certificate_of_indigency';
    public const BARANGAY_CLEARANCE = 'barangay_clearance';
    public const CERTIFICATE_OF_RESIDENCY = 'certificate_of_residency';

    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    public const MAX_PRINTS = 2;

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'print_count' => 'integer',
            'print_limit' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class);
    }

    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(IssuedDocument::class);
    }

    public static function generatePermitNumber(): string
    {
        return 'PERMIT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    public function isCancellable(): bool
    {
        return $this->status === self::PENDING
            && $this->created_at?->addHours(2)->isFuture();
    }

    public function canPrint(): bool
    {
        return $this->status === self::APPROVED
            && $this->print_count < $this->effectivePrintLimit();
    }

    public function printsRemaining(): int
    {
        return max(0, $this->effectivePrintLimit() - $this->print_count);
    }

    public function effectivePrintLimit(): int
    {
        return $this->print_limit ?? self::MAX_PRINTS;
    }
}