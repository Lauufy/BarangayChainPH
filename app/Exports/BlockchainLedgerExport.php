<?php

namespace App\Exports;

use App\Exports\Contracts\AdminExport;
use App\Models\BlockchainRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BlockchainLedgerExport implements AdminExport
{
    public function filename(): string
    {
        return 'blockchain-ledger';
    }

    public function headings(): array
    {
        return [
            'Permit',
            'Issue Transaction',
            'Revoke Transaction',
            'Document Hash',
            'Recorded',
            'Status',
        ];
    }

    public function query(Request $request): Builder
    {
        return BlockchainRecord::query()
            ->with('issuedDocument')
            ->latest('id');
    }

    public function row(Model $model): array
    {
        if (! $model instanceof BlockchainRecord) {
            throw new InvalidArgumentException('Blockchain exports require BlockchainRecord models.');
        }

        $document = $model->issuedDocument;
        $status = match (true) {
            $model->recorded_at !== null && $model->tx_hash === null => 'Hash recovery needed',
            $model->revoked_at !== null => 'Revoked',
            $model->recorded_at !== null => 'Confirmed',
            $model->error !== null => 'Failed',
            default => 'Pending',
        };

        return [
            $document?->document_number.' v'.$document?->version,
            $model->tx_hash ? Str::limit($model->tx_hash, 16) : null,
            $model->revoke_tx_hash ? Str::limit($model->revoke_tx_hash, 16) : null,
            $document?->file_hash ? Str::limit($document->file_hash, 16) : null,
            $model->recorded_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $status,
        ];
    }
}
