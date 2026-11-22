<?php

namespace Tests\Feature\Reports;

use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffAttributionBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_recovers_staff_attribution_from_status_audits_and_leaves_unverifiable_rows_null(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $attributedRequest = ServiceRequest::factory()->create([
            'status' => ServiceRequest::APPROVED,
        ]);
        $unverifiableRequest = ServiceRequest::factory()->create([
            'status' => ServiceRequest::REJECTED,
        ]);
        $missingProcessor = ServiceRequest::factory()->create([
            'status' => ServiceRequest::APPROVED,
            'processed_at' => Carbon::parse('2026-11-09 10:00:00'),
        ]);
        $missingTimestamp = ServiceRequest::factory()->create([
            'status' => ServiceRequest::REJECTED,
            'processed_by' => $staff->id,
        ]);
        $decisionAt = Carbon::parse('2026-11-10 09:30:00');
        foreach ([$attributedRequest, $missingProcessor, $missingTimestamp] as $request) {
            AuditLog::query()->create([
                'user_id' => $staff->id,
                'user_role' => 'admin',
                'action' => 'status_changed',
                'subject_type' => $request->getMorphClass(),
                'subject_id' => (string) $request->id,
                'new_values' => ['status' => $request->status],
                'created_at' => $decisionAt,
            ]);
        }

        $migration = require database_path(
            'migrations/2026_11_13_182721_backfill_staff_attribution_from_audit_logs.php'
        );
        $migration->up();

        $this->assertSame($staff->id, $attributedRequest->fresh()->processed_by);
        $this->assertTrue($decisionAt->equalTo($attributedRequest->fresh()->processed_at));
        $this->assertSame($staff->id, $missingProcessor->fresh()->processed_by);
        $this->assertTrue(Carbon::parse('2026-11-09 10:00:00')->equalTo($missingProcessor->fresh()->processed_at));
        $this->assertSame($staff->id, $missingTimestamp->fresh()->processed_by);
        $this->assertTrue($decisionAt->equalTo($missingTimestamp->fresh()->processed_at));
        $this->assertNull($unverifiableRequest->fresh()->processed_by);
        $this->assertNull($unverifiableRequest->fresh()->processed_at);
        $this->assertSame(1, ServiceRequest::query()
            ->whereIn('status', [ServiceRequest::PROCESSING, ServiceRequest::APPROVED, ServiceRequest::REJECTED])
            ->whereNull('processed_by')
            ->count());
    }
}
