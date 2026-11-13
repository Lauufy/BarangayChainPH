<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Stores a citizen's request for a barangay service or certificate.
 *
 * @property int $id
 * @property string $reference_number
 * @property string $status
 * @property string|null $rejection_reason
 */
#[Fillable([
    'user_id',
    'civil_status',
    'details',
    'contact_number',
    'reference_number',
    'service_type',
    'applicant_name',
    'address',
    'age',
    'purpose',
    'status',
    'approved_at',
    'cancellation_reason',
    'cancelled_at',
    'rejection_reason',
    'permit_number',
    'print_count',
    'print_limit',
])]
class ServiceRequest extends Model
{
    use Auditable, HasFactory;

    // Service types (must match 'type' in config/barangay_services.php)
    public const CERTIFICATE_OF_INDIGENCY = 'certificate_of_indigency';

    public const BARANGAY_CLEARANCE = 'barangay_clearance';

    public const CERTIFICATE_OF_RESIDENCY = 'certificate_of_residency';

    public const BUSINESS_CLEARANCE = 'business_clearance';

    // Statuses
    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const CANCELLED = 'cancelled';

    // Default number of prints allowed per approved request
    public const MAX_PRINTS = 2;

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'details' => 'array',
            'print_count' => 'integer',
            'print_limit' => 'integer',
            'cancelled_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function documents(): HasMany
    {
        return $this->hasMany(ServiceRequestDocument::class);
    }

    public static function generatePermitNumber(): string
    {
        return 'PERMIT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    /** This request's entry in config/barangay_services.php (empty if the service was removed). */
    public function serviceConfig(): array
    {
        return collect(config('barangay_services.services'))->firstWhere('type', $this->service_type) ?? [];
    }

    public function serviceTitle(): string
    {
        return $this->serviceConfig()['title'] ?? 'Barangay Document';
    }

    /** Citizens can cancel only while pending and within two hours of submitting. */
    public function isCancellable(): bool
    {
        return $this->status === self::PENDING
            && (bool) $this->created_at?->gt(now()->subHours(2));
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
