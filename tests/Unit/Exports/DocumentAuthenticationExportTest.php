<?php

namespace Tests\Unit\Exports;

use App\Exports\DocumentAuthenticationExport;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DocumentAuthenticationExportTest extends TestCase
{
    public function test_headings_and_row_omit_document_paths_hashes_and_uuids(): void
    {
        $serviceRequest = new ServiceRequest([
            'service_type' => 'removed_service_type',
            'applicant_name' => 'Alex Citizen',
        ]);
        $document = new IssuedDocument([
            'document_number' => 'DOC-100',
            'version' => 1,
            'issued_at' => '2026-11-16 13:15:00',
            'file_path' => 'private/document.pdf',
            'file_hash' => 'private-hash',
            'document_uuid' => 'private-uuid',
        ]);
        $document->setRelation('serviceRequest', $serviceRequest);

        $export = new DocumentAuthenticationExport;

        $this->assertSame([
            'Document Reference', 'Document Type', 'Submitted By', 'Authentication Status', 'Date',
        ], $export->headings());
        $this->assertSame([
            'DOC-100 v1',
            'Barangay Document',
            'Alex Citizen',
            'Active',
            Carbon::parse('2026-11-16 13:15:00')->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        ], $export->row($document));
    }
}
