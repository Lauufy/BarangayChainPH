<?php

namespace Tests\Unit\Exports;

use App\Exports\AuditLogsExport;
use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditLogsExportTest extends TestCase
{
    public function test_headings_and_row_match_the_readable_audit_history(): void
    {
        $log = new AuditLog([
            'action' => 'service_requests.updated',
            'subject_type' => ServiceRequest::class,
            'subject_id' => '42',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'approved'],
            'created_at' => Carbon::parse('2026-11-16 13:15:00', 'UTC'),
        ]);
        $log->setRelation('user', new User(['name' => 'Alex Staff']));

        $export = new AuditLogsExport;

        $this->assertSame(['Date & Time', 'User', 'Action', 'Record', 'Changes'], $export->headings());
        $this->assertSame([
            $log->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            'Alex Staff',
            'Service request updated',
            'Service request #42',
            'Status: pending -> approved',
        ], $export->row($log));
    }
}
