<?php

namespace Tests\Feature\Reports;

use App\Models\BlockchainRecord;
use App\Models\IdentityVerification;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\VerificationLog;
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
            'processing' => 0,
            'in_progress' => 0,
            'approved' => 0,
            'rejected' => 0,
            'cancelled' => 0,
            'approval_rate' => 0.0,
            'rejection_rate' => 0.0,
            'decisions' => 0,
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
            'processed_at' => $from->copy()->addHours(2),
        ]);
        ServiceRequest::factory()->create([
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'status' => ServiceRequest::APPROVED,
            'created_at' => Carbon::parse('2026-11-02 08:00:00'),
            'processed_at' => Carbon::parse('2026-11-02 18:00:00'),
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
        $this->assertSame(1, $summary['pending']);
        $this->assertSame(1, $summary['processing']);
        $this->assertSame(2, $summary['in_progress']);
        $this->assertSame(2, $summary['approved']);
        $this->assertSame(1, $summary['rejected']);
        $this->assertSame(6.0, $summary['average_processing_hours']);
        $this->assertSame(66.7, $summary['approval_rate']);
        $this->assertSame(33.3, $summary['rejection_rate']);

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
            [...$this->filters('2026-01-15', '2026-03-02'), 'grouping' => 'monthly']
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
        $pendingDocument = IssuedDocument::factory()->create(['service_request_id' => $pendingRequest->id]);
        BlockchainRecord::factory()->create(['issued_document_id' => $pendingDocument->id]);

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

        $reports = app(ReportService::class);
        $this->assertSame([
            'recorded' => 1,
            'pending' => 1,
            'failed' => 1,
        ], $reports->blockchainActivity($filters));
        $transactions = $reports->blockchainTransactions($filters);
        $this->assertSame(4, $transactions['total']);
        $this->assertSame(1, $transactions['revoked']);
        $this->assertCount(4, $transactions['recent']);
        $successfulTransaction = collect($transactions['recent'])
            ->firstWhere('reference_number', $recordedRequest->reference_number);
        $this->assertSame('Successful', $successfulTransaction['status']);
        $this->assertSame('0xrecorded', $successfulTransaction['transaction_hash']);
    }

    public function test_yearly_grouping_and_previous_period_filters_are_continuous(): void
    {
        ServiceRequest::factory()->create(['created_at' => Carbon::parse('2024-06-01')]);
        ServiceRequest::factory()->create(['created_at' => Carbon::parse('2025-01-01')]);

        $filters = [...$this->filters('2025-01-01', '2025-12-31'), 'grouping' => 'yearly'];
        $trends = app(ReportService::class)->trends($filters);
        $previous = app(ReportService::class)->previousPeriodFilters($filters);

        $this->assertSame(['2025'], $trends['labels']);
        $this->assertSame([1], $trends['total']);
        $this->assertSame('2024-01-02', $previous['from']->toDateString());
        $this->assertSame('2024-12-31', $previous['to']->toDateString());
    }

    public function test_latest_citizen_identity_states_and_review_rates_use_review_dates(): void
    {
        $verifiedCitizen = User::factory()->create(['role' => 'citizen']);
        $pendingCitizen = User::factory()->create(['role' => 'citizen']);
        $rejectedCitizen = User::factory()->create(['role' => 'citizen']);
        $unverifiedCitizen = User::factory()->create(['role' => 'citizen']);
        $this->createIdentity($verifiedCitizen, IdentityVerification::REJECTED, Carbon::parse('2026-11-09'));
        $this->createIdentity($verifiedCitizen, IdentityVerification::VERIFIED, Carbon::parse('2026-11-10'));
        $this->createIdentity($pendingCitizen, IdentityVerification::PENDING, null);
        $this->createIdentity($rejectedCitizen, IdentityVerification::REJECTED, Carbon::parse('2026-10-10'));

        $metrics = app(ReportService::class)->citizenVerification($this->filters('2026-11-01', '2026-11-13'));

        $this->assertSame(4, $metrics['total']);
        $this->assertSame(1, $metrics['verified']);
        $this->assertSame(1, $metrics['pending']);
        $this->assertSame(1, $metrics['rejected']);
        $this->assertSame(1, $metrics['unverified']);
        $this->assertSame(50.0, $metrics['approval_rate']);
        $this->assertSame(50.0, $metrics['rejection_rate']);
        $this->assertSame(2, $metrics['review_count']);
        $this->assertSame('Selected period', $metrics['review_period_label']);
    }

    public function test_staff_metrics_only_include_attributed_requests_and_terminal_processing_times(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::APPROVED,
            'processed_by' => $staff->id,
            'created_at' => Carbon::parse('2026-11-10'),
            'processed_at' => Carbon::parse('2026-11-10 06:00:00'),
        ]);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PROCESSING,
            'processed_by' => $staff->id,
            'created_at' => Carbon::parse('2026-11-11'),
            'processed_at' => Carbon::parse('2026-11-11 01:00:00'),
        ]);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::REJECTED,
            'processed_by' => null,
            'created_at' => Carbon::parse('2026-11-12'),
        ]);
        ServiceRequest::factory()->create([
            'status' => ServiceRequest::PENDING,
            'created_at' => now()->subDays(5),
        ]);

        $reports = app(ReportService::class);
        $summary = $reports->summary($this->filters('2026-11-01', '2026-11-13'));
        $performance = $reports->staffPerformance($this->filters('2026-11-01', '2026-11-13'));
        $staffAccess = $reports->staffAccess($staff);

        $this->assertSame(6.0, $summary['average_processing_hours']);
        $this->assertSame(1, $performance['unattributed']);
        $this->assertSame([
            [
                'staff_name' => $staff->name,
                'processed' => 2,
                'approved' => 1,
                'rejected' => 0,
                'average_processing_hours' => 6.0,
            ],
        ], $performance['staff']);
        $this->assertSame(2, $staffAccess['awaiting_action']);
        $this->assertSame(5, $staffAccess['oldest_pending_age_days']);
    }

    public function test_zero_completed_decisions_produce_zero_rates_and_no_processing_average(): void
    {
        ServiceRequest::factory()->create(['status' => ServiceRequest::PROCESSING]);

        $summary = app(ReportService::class)->summary($this->filters('2026-11-01', '2026-11-13'));

        $this->assertSame(0, $summary['decisions']);
        $this->assertSame(0.0, $summary['approval_rate']);
        $this->assertSame(0.0, $summary['rejection_rate']);
        $this->assertNull($summary['average_processing_hours']);
    }

    public function test_document_verification_counts_period_attempts_and_latest_request_results(): void
    {
        $validRequest = $this->approvedRequest();
        IssuedDocument::factory()->create([
            'service_request_id' => $validRequest->id,
            'issued_at' => Carbon::parse('2026-11-01 00:00:00'),
        ]);
        $otherRequest = $this->approvedRequest();
        IssuedDocument::factory()->create([
            'service_request_id' => $otherRequest->id,
            'issued_at' => Carbon::parse('2026-10-31 23:59:59'),
            'revoked_at' => Carbon::parse('2026-11-13 10:00:00'),
        ]);
        $latestRequest = $this->approvedRequest();
        IssuedDocument::factory()->create([
            'service_request_id' => $latestRequest->id,
            'issued_at' => Carbon::parse('2026-11-13 23:59:59'),
        ]);
        $outsideRequest = $this->approvedRequest();
        IssuedDocument::factory()->create([
            'service_request_id' => $outsideRequest->id,
            'issued_at' => Carbon::parse('2026-10-31 23:59:59'),
        ]);

        VerificationLog::query()->create([
            'service_request_id' => $validRequest->id,
            'result' => 'valid',
            'created_at' => Carbon::parse('2026-11-02'),
        ]);
        VerificationLog::query()->create([
            'service_request_id' => $validRequest->id,
            'result' => 'invalid',
            'created_at' => Carbon::parse('2026-11-03'),
        ]);
        VerificationLog::query()->create([
            'service_request_id' => $otherRequest->id,
            'result' => 'revoked',
            'created_at' => Carbon::parse('2026-11-12'),
        ]);
        VerificationLog::query()->create([
            'service_request_id' => $otherRequest->id,
            'result' => 'unavailable',
            'created_at' => Carbon::parse('2026-11-13'),
        ]);
        VerificationLog::query()->create([
            'service_request_id' => null,
            'result' => 'not_found',
            'created_at' => Carbon::parse('2026-11-13'),
        ]);
        VerificationLog::query()->create([
            'service_request_id' => $latestRequest->id,
            'result' => 'pending',
            'created_at' => Carbon::parse('2026-11-13 11:00:00'),
        ]);

        $metrics = app(ReportService::class)->documentVerification($this->filters('2026-11-01', '2026-11-13'));

        $this->assertSame(2, $metrics['documents_issued']);
        $this->assertSame(6, $metrics['verification_attempts']);
        $this->assertSame(1, $metrics['valid']);
        $this->assertSame(1, $metrics['invalid']);
        $this->assertSame(1, $metrics['revoked']);
        $this->assertSame(3, $metrics['other']);
        $this->assertSame(16.7, $metrics['valid_rate']);
        $this->assertSame(1, $metrics['documents_revoked']);
        $this->assertSame(1, $metrics['latest_results']['invalid']);
        $this->assertSame(1, $metrics['latest_results']['pending']);
        $this->assertSame(0, $metrics['latest_results']['unavailable']);
        $this->assertSame(0, $metrics['latest_results']['valid']);
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

    private function createIdentity(User $user, string $status, ?Carbon $reviewedAt): void
    {
        IdentityVerification::query()->create([
            'user_id' => $user->id,
            'status' => $status,
            'document_type' => 'national_id',
            'document_path' => 'identity/test.pdf',
            'submitted_at' => Carbon::parse('2026-10-01'),
            'reviewed_at' => $reviewedAt,
        ]);
    }
}
