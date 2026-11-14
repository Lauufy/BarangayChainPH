<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'version',
    'network',
    'contract_address',
    'deploy_tx_hash',
    'is_active',
    'deployed_at',
    'notes',
])]
class ContractDeployment extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deployed_at' => 'datetime',
        ];
    }
}
