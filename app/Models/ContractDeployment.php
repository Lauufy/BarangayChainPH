<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'active_network', 'owner_address', 'synced_at',
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

    protected $hidden = ['pending_control'];

    public static function network(): string
    {
        $network = config('blockchain.network');

        return $network === 'live' ? 'sepolia' : $network;
    }

    public static function active(): ?self
    {
        return self::where('active_network', self::network())->first();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'pending_control' => 'encrypted:array',
            'deployed_at' => 'datetime',
        ];
    }
}
