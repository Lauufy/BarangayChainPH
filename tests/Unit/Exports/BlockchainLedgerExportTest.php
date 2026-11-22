<?php

namespace Tests\Unit\Exports;

use App\Exports\BlockchainLedgerExport;
use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BlockchainLedgerExportTest extends TestCase
{
    public function test_headings_and_row_match_the_ledger_columns_without_private_keys(): void
    {
        $document = new IssuedDocument([
            'document_number' => 'DOC-100',
            'version' => 2,
            'file_hash' => 'sha256-hash',
        ]);
        $record = new BlockchainRecord([
            'tx_hash' => 'issue-hash',
            'revoke_tx_hash' => 'revoke-hash',
            'recorded_at' => '2026-11-16 13:15:00',
        ]);
        $record->setRelation('issuedDocument', $document);

        $export = new BlockchainLedgerExport;

        $this->assertSame([
            'Permit', 'Issue Transaction', 'Revoke Transaction', 'Document Hash', 'Recorded', 'Status',
        ], $export->headings());
        $this->assertSame([
            'DOC-100 v2',
            'issue-hash',
            'revoke-hash',
            'sha256-hash',
            Carbon::parse('2026-11-16 13:15:00')->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'Confirmed',
        ], $export->row($record));
    }
}
