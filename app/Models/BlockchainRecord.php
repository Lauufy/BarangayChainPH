<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'issued_document_id',
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
    public const TX_HASH_RECOVERY_ERROR = 'Issuance transaction hash recovery failed:';

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function issuedDocument(): BelongsTo
    {
        return $this->belongsTo(IssuedDocument::class);
    }
}
