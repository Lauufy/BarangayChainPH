<?php

namespace Tests\Unit\Exports;

use App\Exports\PermitsExport;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PermitsExportTest extends TestCase
{
    public function test_headings_and_row_match_the_permit_list_fields(): void
    {
        $submittedAt = Carbon::parse('2026-11-16 13:15:00', 'UTC');
        $request = new ServiceRequest;
        $request->setRawAttributes([
            'reference_number' => 'BRGY-001',
            'applicant_name' => 'Alex Citizen',
            'service_type' => 'removed_service_type',
            'created_at' => $submittedAt,
            'status' => 'pending',
        ]);
        $request->setRelation('user', new User(['email' => 'alex@example.test']));

        $export = new PermitsExport;

        $this->assertSame([
            'Reference', 'Applicant', 'Applicant Email', 'Document Type', 'Date Submitted', 'Status',
        ], $export->headings());
        $this->assertSame([
            'BRGY-001',
            'Alex Citizen',
            'alex@example.test',
            'Barangay Document',
            $submittedAt->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'pending',
        ], $export->row($request));
    }
}
