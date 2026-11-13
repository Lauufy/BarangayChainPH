<?php

namespace Tests\Feature\Reports;

use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Services\InsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InsightServiceTest extends TestCase
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

    public function test_no_data_and_exact_backlog_threshold_do_not_raise_insights(): void
    {
        $filters = $this->filters('2026-10-01', '2026-11-13');
        $insights = app(InsightService::class);

        $this->assertSame([], $insights->insights($filters));

        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PENDING,
            'created_at' => now()->subDays(3),
        ]);

        $this->assertSame([], $insights->insights($filters));
    }

    public function test_backlog_alert_only_includes_items_older_than_configured_days(): void
    {
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PROCESSING,
            'created_at' => now()->subDays(3)->subSecond(),
        ]);

        $insight = $this->insight('backlog', $this->filters('2026-11-13', '2026-11-13'));

        $this->assertStringContainsString('1 request awaiting action is over 3 days old', $insight['message']);
        $this->assertSame(route('admin.permits.index'), $insight['link']);
    }

    public function test_high_rejection_rate_requires_more_than_the_rate_and_minimum_volume_thresholds(): void
    {
        for ($request = 0; $request < 9; $request++) {
            ServiceRequest::factory()->create([
                'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
                'status' => ServiceRequest::REJECTED,
                'created_at' => Carbon::parse('2026-11-10'),
            ]);
        }
        for ($request = 0; $request < 10; $request++) {
            ServiceRequest::factory()->create([
                'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
                'status' => $request < 3 ? ServiceRequest::REJECTED : ServiceRequest::APPROVED,
                'created_at' => Carbon::parse('2026-11-10'),
            ]);
        }

        $filters = $this->filters('2026-11-07', '2026-11-13');
        $this->assertNull($this->findInsight('high_rejection_rate', app(InsightService::class)->insights($filters)));

        ServiceRequest::factory()->create([
            'service_type' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'status' => ServiceRequest::REJECTED,
            'created_at' => Carbon::parse('2026-11-10'),
        ]);

        $insight = $this->insight('high_rejection_rate', $filters);
        $this->assertStringContainsString('Certificate of Residency: 36% rejected (4/11).', $insight['message']);
    }

    public function test_blockchain_health_reports_failed_and_overdue_pending_items(): void
    {
        $failedRequest = $this->approvedRequest();
        $failedDocument = IssuedDocument::factory()->create([
            'service_request_id' => $failedRequest->id,
            'issued_at' => now(),
        ]);
        BlockchainRecord::factory()->create([
            'issued_document_id' => $failedDocument->id,
            'error' => 'transaction failed',
        ]);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PENDING,
            'created_at' => now()->subDays(5),
        ]);

        $pendingRequest = $this->approvedRequest();
        IssuedDocument::factory()->create([
            'service_request_id' => $pendingRequest->id,
            'issued_at' => now()->subMinutes(30),
        ]);
        $atThreshold = $this->insight('blockchain_health', $this->filters('2026-11-13', '2026-11-13'));
        $this->assertSame('Blockchain: 1 failed, 0 stuck.', $atThreshold['message']);

        IssuedDocument::factory()->create([
            'service_request_id' => $pendingRequest->id,
            'version' => 2,
            'document_uuid' => 'd1b3cd7d-e696-4a46-98da-8467f6845a43',
            'document_number' => 'PERMIT-SECOND',
            'issued_at' => now()->subMinutes(31),
        ]);

        $insights = app(InsightService::class)->insights($this->filters('2026-11-13', '2026-11-13'));
        $this->assertSame(['blockchain_health', 'backlog'], array_column($insights, 'type'));
        $this->assertSame('critical', $insights[0]['severity']);
        $this->assertSame('Blockchain: 1 failed, 1 stuck.', $insights[0]['message']);
        $this->assertSame(route('admin.blockchain-ledger.index'), $insights[0]['link']);
    }

    public function test_insights_are_severity_ordered_and_limited_to_three(): void
    {
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PENDING,
            'created_at' => now()->subDays(4),
        ]);

        foreach ([
            ServiceRequest::BARANGAY_CLEARANCE,
            ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            ServiceRequest::BUSINESS_CLEARANCE,
        ] as $type) {
            for ($request = 0; $request < 10; $request++) {
                ServiceRequest::factory()->create([
                    'service_type' => $type,
                    'status' => $request < 4 ? ServiceRequest::REJECTED : ServiceRequest::APPROVED,
                    'created_at' => now()->subDay(),
                ]);
            }
        }

        $failedRequest = $this->approvedRequest();
        $failedDocument = IssuedDocument::factory()->create([
            'service_request_id' => $failedRequest->id,
            'issued_at' => now(),
        ]);
        BlockchainRecord::factory()->create([
            'issued_document_id' => $failedDocument->id,
            'error' => 'transaction failed',
        ]);

        $insights = app(InsightService::class)->insights($this->filters('2026-11-01', '2026-11-13'));

        $this->assertCount(3, $insights);
        $this->assertSame(['critical', 'warning', 'warning'], array_column($insights, 'severity'));
        $this->assertSame('blockchain_health', $insights[0]['type']);
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

    /**
     * @param  list<array{type: string, severity: string, message: string, link: ?string}>  $insights
     * @return array{type: string, severity: string, message: string, link: ?string}|null
     */
    private function findInsight(string $type, array $insights): ?array
    {
        foreach ($insights as $insight) {
            if ($insight['type'] === $type) {
                return $insight;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, severity: string, message: string, link: ?string}
     */
    private function insight(string $type, array $filters): array
    {
        $insight = $this->findInsight($type, app(InsightService::class)->insights($filters));
        $this->assertNotNull($insight);

        return $insight;
    }

    private function approvedRequest(): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'status' => ServiceRequest::APPROVED,
            'created_at' => Carbon::parse('2026-11-10'),
        ]);
    }
}
