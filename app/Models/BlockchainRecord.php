<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'issued_document_id',
    'signed_revocation', 'revoke_submitted_at', 'signed_transaction', 'submitted_at', 'previous_attempts',
    'network',
    'contract',
    'tx_hash',
    'recorded_at',
    'revoke_tx_hash',
    'revoked_at',
    'error',
])]
/** Where one issued permit is recorded on the blockchain. */
class BlockchainRecord extends Model
{
    use HasFactory;

    public const STATE_SQL = "case when revoked_at is not null then 'revoked'
        when revoke_tx_hash is not null then 'submitted' when error like 'Blockchain revoke failed:%' then 'failed'
        when recorded_at is not null then 'confirmed' when tx_hash is not null then 'submitted'
        when error like '%ContractPaused%' then 'paused' when error is not null then 'failed' else 'pending' end";

    public const TX_HASH_RECOVERY_ERROR = 'Issuance transaction hash recovery failed:';

    protected $hidden = ['signed_transaction', 'signed_revocation'];

    public function issuanceState(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'revoked',
            filled($this->revoke_tx_hash) => 'submitted',
            str_starts_with($this->error ?? '', 'Blockchain revoke failed:') => 'failed',
            $this->recorded_at !== null => 'confirmed',
            filled($this->tx_hash) => 'submitted',
            str_contains($this->error ?? '', 'ContractPaused') => 'paused',
            filled($this->error) => 'failed',
            default => 'pending',
        };
    }

    protected static function booted(): void
    {
        static::updating(function (self $record) {
            if ($record->isDirty('error') && filled($record->getOriginal('error'))) {
                $record->previous_attempts = [...($record->previous_attempts ?? []), [
                    'network' => $record->getOriginal('network'),
                    'contract' => $record->getOriginal('contract'),
                    'hash' => $record->getOriginal('revoke_tx_hash') ?? $record->getOriginal('tx_hash'),
                    'time' => $record->getOriginal('updated_at'),
                    'error' => $record->getOriginal('error'),
                ]];
            }
        });
    }

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'signed_transaction' => 'encrypted',
            'signed_revocation' => 'encrypted',
            'revoke_submitted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'previous_attempts' => 'array',
            'revoked_at' => 'datetime',
        ];
    }

    public function anchorNetwork(): string
    {
        return $this->network && $this->network !== 'live' ? $this->network : 'sepolia';
    }

    public function operationMessage(): ?string
    {
        if (! $this->error) {
            return null;
        }

        return match (true) {
            str_contains($this->error, 'ContractPaused') => 'Held until recording resumes.',
            str_contains($this->error, 'INSUFFICIENT_FUNDS') => 'Insufficient issuer balance.',
            str_contains(strtolower($this->error), 'timeout') => 'Confirmation timed out.',
            str_contains($this->error, 'reverted') => 'Transaction reverted.',
            str_contains($this->error, self::TX_HASH_RECOVERY_ERROR) => 'Issuance hash needs recovery.',
            default => 'Reconciliation required. Check the worker and RPC connection.',
        };
    }

    public function transactionUrl(?string $hash): ?string
    {
        $explorer = config('blockchain.networks.'.$this->anchorNetwork().'.explorer_url');

        return filled($hash) && filled($explorer)
            ? rtrim($explorer, '/').'/tx/'.rawurlencode($hash)
            : null;
    }

    public function issuedDocument(): BelongsTo
    {
        return $this->belongsTo(IssuedDocument::class);
    }
}
