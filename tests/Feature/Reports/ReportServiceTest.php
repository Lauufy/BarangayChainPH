<?php

namespace Tests\Feature\Reports;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-13 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_empty_database_returns_zeroes_and_continuous_daily_buckets(): void
    {
        $filters = $this->filters('2026-11-01', '2026-11-30');
        $reports = app(ReportService::class);

        $this->assertSame([
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'average_processing_hours' => null,
        ], $reports->summary($filters));
        $this->assertCount(30, $reports->byPeriod($filters)['labels']);
        $this->assertSame(array_fill(0, 30, 0), $reports->byPeriod($filters)['values']);
        $this->assertSame([
            'recorded' => 0,
            'pending' => 0,
            'failed' => 0,
        ], $reports->blockchainActivity($filters));
    }

    public function test_filters_include_both_date_boundaries_and_pending_includes_processing(): void
    {
        $from = Carbon::parse('2026-11-01 00:00:00');
        ServiceRequest::factory()->create([
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'status' => ServiceRequest::APPROVED,
            'created_at' => $from,
            'approved_at' => $from->copy()->addHours(2),
        ]);
        ServiceRequest::factory()->create([
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'status' => ServiceRequest::APPROVED,
            'created_at' => Carbon::parse('2026-11-02 08:00:00'),
            'approved_at' => Carbon::parse('2026-11-02 18:00:00'),
        ]);
        ServiceRequest::factory()->create([
            'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'status' => ServiceRequest::REJECTED,
            'created_at' => Carbon::parse('2026-11-03 23:59:59'),
        ]);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PENDING,
            'created_at' => Carbon::parse('2026-11-03 10:00:00'),
        ]);
        ServiceRequest::factory()->create([
            'service_type' => ServiceRequest::BUSINESS_CLEARANCE,
            'status' => ServiceRequest::PROCESSING,
            'created_at' => Carbon::parse('2026-11-03 11:00:00'),
        ]);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PENDING,
            'created_at' => Carbon::parse('2026-10-31 23:59:59'),
        ]);

        $reports = app(ReportService::class);
        $filters = $this->filters('2026-11-01', '2026-11-03');
        $summary = $reports->summary($filters);

        $this->assertSame(5, $summary['total']);
        $this->assertSame(2, $summary['pending']);
        $this->assertSame(2, $summary['approved']);
        $this->assertSame(1, $summary['rejected']);
        $this->assertSame(6.0, $summary['average_processing_hours']);

        $typeCounts = collect($reports->byDocumentType($filters))->keyBy('type');
        $this->assertSame(3, $typeCounts[ServiceRequest::BARANGAY_CLEARANCE]['value']);
        $this->assertSame(1, $typeCounts[ServiceRequest::CERTIFICATE_OF_RESIDENCY]['value']);

        $filtered = $reports->summary(array_merge($filters, [
            'document_type' => ServiceRequest::BARANGAY_CLEARANCE,
        ]));
        $this->assertSame(3, $filtered['total']);
    }

    public function test_period_buckets_switch_to_monthly_and_fill_empty_months(): void
    {
        ServiceRequest::factory()->create(['created_at' => Carbon::parse('2026-01-15')]);
        ServiceRequest::factory()->create(['created_at' => Carbon::parse('2026-03-02')]);

        $period = app(ReportService::class)->byPeriod(
            $this->filters('2026-01-15', '2026-03-02')
        );

        $this->assertSame('monthly', $period['granularity']);
        $this->assertSame(['Jan 2026', 'Feb 2026', 'Mar 2026'], $period['labels']);
        $this->assertSame([1, 0, 1], $period['values']);
    }

    public function test_blockchain_summary_counts_recorded_pending_and_failed_documents(): void
    {
        $filters = $this->filters('2026-11-01', '2026-11-13');

        $recordedRequest = $this->approvedRequest();
        $recordedDocument = IssuedDocument::factory()->create(['service_request_id' => $recordedRequest->id]);
        BlockchainRecord::factory()->create([
            'issued_document_id' => $recordedDocument->id,
            'recorded_at' => now(),
            'tx_hash' => '0xrecorded',
        ]);

        $pendingRequest = $this->approvedRequest();
        IssuedDocument::factory()->create(['service_request_id' => $pendingRequest->id]);

        $failedRequest = $this->approvedRequest();
        $failedDocument = IssuedDocument::factory()->create(['service_request_id' => $failedRequest->id]);
        BlockchainRecord::factory()->create([
            'issued_document_id' => $failedDocument->id,
            'error' => 'temporary failure',
        ]);

        $revokedRequest = $this->approvedRequest();
        $revokedDocument = IssuedDocument::factory()->create([
            'service_request_id' => $revokedRequest->id,
            'revoked_at' => now(),
        ]);
        BlockchainRecord::factory()->create([
            'issued_document_id' => $revokedDocument->id,
            'revoked_at' => now(),
            'revoke_tx_hash' => '0xrevoke',
        ]);

        $this->assertSame([
            'recorded' => 1,
            'pending' => 1,
            'failed' => 1,
        ], app(ReportService::class)->blockchainActivity($filters));
    }

    /**
     * @return array{from: Carbon, to: Carbon, document_type: ?string}
     */
    private function filters(string $from, string $to): array
    {
        return [
            'from' => Carbon::parse($from)->startOfDay(),
            'to' => Carbon::parse($to)->endOfDay(),
            'document_type' => null,
        ];
    }

    private function approvedRequest(): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'status' => ServiceRequest::APPROVED,
            'created_at' => Carbon::parse('2026-11-10'),
        ]);
    }
}
