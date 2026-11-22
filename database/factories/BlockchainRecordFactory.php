<?php

namespace Database\Factories;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockchainRecord>
 */
class BlockchainRecordFactory extends Factory
{
    protected $model = BlockchainRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issued_document_id' => IssuedDocument::factory(),
            'network' => 'test',
            'contract' => null,
            'tx_hash' => null,
            'recorded_at' => null,
            'revoke_tx_hash' => null,
            'revoked_at' => null,
            'error' => null,
        ];
    }
}
