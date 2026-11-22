<?php

namespace App\Services;

use App\Models\BlockchainRecord;
use App\Models\IdentityVerification;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\VerificationLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float|null>
     */
    public function summary(array $filters): array
    {
        $counts = $this->requestQuery($filters)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $approved = (int) ($counts[ServiceRequest::APPROVED] ?? 0);
        $rejected = (int) ($counts[ServiceRequest::REJECTED] ?? 0);
        $decisions = $approved + $rejected;

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts[ServiceRequest::PENDING] ?? 0),
            'processing' => (int) ($counts[ServiceRequest::PROCESSING] ?? 0),
            'in_progress' => (int) ($counts[ServiceRequest::PENDING] ?? 0)
                + (int) ($counts[ServiceRequest::PROCESSING] ?? 0),
            'approved' => $approved,
            'rejected' => $rejected,
            'cancelled' => (int) ($counts[ServiceRequest::CANCELLED] ?? 0),
            'approval_rate' => $decisions === 0 ? 0.0 : round(($approved / $decisions) * 100, 1),
            'rejection_rate' => $decisions === 0 ? 0.0 : round(($rejected / $decisions) * 100, 1),
            'decisions' => $decisions,
            'average_processing_hours' => $this->averageProcessingHours($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{type: string, label: string, value: int}>
     */
    public function byPermitType(array $filters): array
    {
        $counts = $this->requestQuery($filters)
            ->selectRaw('service_type, COUNT(*) AS aggregate')
            ->groupBy('service_type')
            ->pluck('aggregate', 'service_type');
        $titles = $this->documentTypeTitles();

        foreach ($counts->keys() as $type) {
            $titles[$type] ??= $type;
        }

        return collect($titles)->map(fn (string $title, string $type) => [
            'type' => $type,
            'label' => $title,
            'value' => (int) ($counts[$type] ?? 0),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{type: string, label: string, value: int}>
     */
    public function byDocumentType(array $filters): array
    {
        return $this->byPermitType($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   granularity: string,
     *   labels: list<string>,
     *   total: list<int>,
     *   in_progress: list<int>,
     *   approved: list<int>,
     *   average_processing_hours: list<?float>
     * }
     */
    public function trends(array $filters): array
    {
        $buckets = $this->emptyTrendBuckets($filters);
        $granularity = $filters['grouping'] ?? 'daily';

        foreach (
            $this->requestQuery($filters)
                ->select(['created_at', 'processed_at', 'status'])
                ->orderBy('created_at')
                ->cursor() as $request
        ) {
            $createdAt = Carbon::parse($request->created_at);
            $key = $this->periodKey($createdAt, $granularity);
            $buckets[$key]['total']++;

            if ($request->status === ServiceRequest::PENDING || $request->status === ServiceRequest::PROCESSING) {
                $buckets[$key]['in_progress']++;
            }

            if ($request->status === ServiceRequest::APPROVED) {
                $buckets[$key]['approved']++;
            }

            if (
                in_array($request->status, [ServiceRequest::APPROVED, ServiceRequest::REJECTED], true)
                && $request->processed_at !== null
            ) {
                $hours = $this->processingHours($createdAt, Carbon::parse($request->processed_at));

                if ($hours !== null) {
                    $buckets[$key]['processing_hours_sum'] += $hours;
                    $buckets[$key]['processing_hours_count']++;
                }
            }
        }

        $values = array_values($buckets);

        return [
            'granularity' => $granularity,
            'labels' => array_column($values, 'label'),
            'total' => array_column($values, 'total'),
            'in_progress' => array_column($values, 'in_progress'),
            'approved' => array_column($values, 'approved'),
            'average_processing_hours' => array_map(
                fn (array $bucket): ?float => $bucket['processing_hours_count'] === 0
                    ? null
                    : round($bucket['processing_hours_sum'] / $bucket['processing_hours_count'], 2),
                $values
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{granularity: string, labels: list<string>, values: list<int>}
     */
    public function byPeriod(array $filters): array
    {
        $trends = $this->trends($filters);

        return [
            'granularity' => $trends['granularity'],
            'labels' => $trends['labels'],
            'values' => $trends['total'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function previousPeriodFilters(array $filters): array
    {
        $start = $this->from($filters)->startOfDay();
        $days = (int) $start->diffInDays($this->to($filters)->startOfDay()) + 1;
        $previousStart = $start->copy()->subDays($days);

        return [
            ...$filters,
            'from' => $previousStart,
            'to' => $previousStart->copy()->addDays($days - 1)->endOfDay(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   total: int,
     *   verified: int,
     *   pending: int,
     *   rejected: int,
     *   unverified: int,
     *   approval_rate: float,
     *   rejection_rate: float,
     *   review_count: int,
     *   review_period_label: string
     * }
     */
    public function citizenVerification(array $filters): array
    {
        $totalCitizens = User::query()->where('role', 'citizen')->count();
        $latestIds = IdentityVerification::query()
            ->selectRaw('MAX(id)')
            ->groupBy('user_id');
        $identityCounts = IdentityVerification::query()
            ->whereIn('id', $latestIds)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $verified = (int) ($identityCounts[IdentityVerification::VERIFIED] ?? 0);
        $pending = (int) ($identityCounts[IdentityVerification::PENDING] ?? 0);
        $rejected = (int) ($identityCounts[IdentityVerification::REJECTED] ?? 0);

        $decisions = IdentityVerification::query()
            ->whereIn('status', [IdentityVerification::VERIFIED, IdentityVerification::REJECTED])
            ->whereNotNull('reviewed_at');
        $hasReviewTimestamps = (clone $decisions)->exists();
        $reviewPeriodLabel = 'All Time';

        if ($hasReviewTimestamps) {
            $decisions->whereBetween('reviewed_at', [
                $this->from($filters)->startOfDay(),
                $this->to($filters)->endOfDay(),
            ]);
            $reviewPeriodLabel = 'Selected period';
        }

        $reviewCounts = (clone $decisions)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $approvedReviews = (int) ($reviewCounts[IdentityVerification::VERIFIED] ?? 0);
        $rejectedReviews = (int) ($reviewCounts[IdentityVerification::REJECTED] ?? 0);
        $reviewCount = $approvedReviews + $rejectedReviews;

        return [
            'total' => $totalCitizens,
            'verified' => $verified,
            'pending' => $pending,
            'rejected' => $rejected,
            'unverified' => max(0, $totalCitizens - $verified - $pending - $rejected),
            'approval_rate' => $reviewCount === 0 ? 0.0 : round(($approvedReviews / $reviewCount) * 100, 1),
            'rejection_rate' => $reviewCount === 0 ? 0.0 : round(($rejectedReviews / $reviewCount) * 100, 1),
            'review_count' => $reviewCount,
            'review_period_label' => $reviewPeriodLabel,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   total: int,
     *   successful: int,
     *   failed: int,
     *   pending: int,
     *   revoked: int,
     *   recent: list<array{
     *     reference_number: string,
     *     document_type: string,
     *     status: string,
     *     transaction_hash: ?string,
     *     explorer_url: ?string,
     *     date: ?Carbon
     *   }>
     * }
     */
    public function blockchainTransactions(array $filters): array
    {
        $query = $this->filteredBlockchainRecords($filters);
        $counts = [
            'successful' => (clone $query)->whereNotNull('recorded_at')->whereNull('error')->whereNull('revoked_at')->count(),
            'failed' => (clone $query)->whereNotNull('error')->whereNull('revoked_at')->count(),
            'pending' => (clone $query)->whereNull('recorded_at')->whereNull('error')->whereNull('revoked_at')->count(),
            'revoked' => (clone $query)->whereNotNull('revoked_at')->count(),
        ];

        $recent = (clone $query)
            ->with(['issuedDocument.serviceRequest'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (BlockchainRecord $record): array {
                $request = $record->issuedDocument?->serviceRequest;
                $status = match (true) {
                    $record->revoked_at !== null => 'Revoked',
                    $record->error !== null => 'Failed',
                    $record->recorded_at === null => 'Pending',
                    default => 'Successful',
                };
                $hash = $record->revoke_tx_hash ?? $record->tx_hash;
                $explorerUrl = filled($hash)
                    ? rtrim((string) config('blockchain.explorer_url'), '/').'/tx/'.rawurlencode($hash)
                    : null;

                return [
                    'reference_number' => $request?->reference_number ?? 'Unavailable',
                    'document_type' => $request?->serviceTitle() ?? 'Unavailable',
                    'status' => $status,
                    'transaction_hash' => $this->truncateHash($hash),
                    'explorer_url' => $explorerUrl,
                    'date' => $record->recorded_at ?? $record->created_at,
                ];
            })
            ->all();

        return [
            'total' => array_sum($counts),
            ...$counts,
            'recent' => $recent,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   documents_issued: int,
     *   verification_attempts: int,
     *   valid: int,
     *   invalid: int,
     *   revoked: int,
     *   other: int,
     *   valid_rate: float,
     *   documents_revoked: int,
     *   latest_results: array<string, int>
     * }
     */
    public function documentVerification(array $filters): array
    {
        $documents = $this->issuedDocuments($filters);
        $logs = VerificationLog::query()
            ->whereBetween('created_at', [
                $this->from($filters)->startOfDay(),
                $this->to($filters)->endOfDay(),
            ])
            ->when(
                $this->permitType($filters) !== null,
                fn (Builder $query) => $query->whereHas(
                    'serviceRequest',
                    fn (Builder $request) => $request->where('service_type', $this->permitType($filters))
                )
            );
        $resultCounts = (clone $logs)
            ->selectRaw('result, COUNT(*) AS aggregate')
            ->groupBy('result')
            ->pluck('aggregate', 'result');
        $valid = (int) ($resultCounts['valid'] ?? 0);
        $invalid = (int) ($resultCounts['invalid'] ?? 0);
        $revoked = (int) ($resultCounts['revoked'] ?? 0);
        $attempts = (int) $resultCounts->sum();
        $serviceRequestIds = (clone $documents)->select('service_request_id')->distinct();
        $latestLogIds = VerificationLog::query()
            ->whereNotNull('service_request_id')
            ->whereIn('service_request_id', $serviceRequestIds)
            ->selectRaw('MAX(id)')
            ->groupBy('service_request_id');
        $latestResults = VerificationLog::query()
            ->whereIn('id', $latestLogIds)
            ->selectRaw('result, COUNT(*) AS aggregate')
            ->groupBy('result')
            ->pluck('aggregate', 'result');
        $latestResultCounts = [
            'valid' => (int) ($latestResults['valid'] ?? 0),
            'invalid' => (int) ($latestResults['invalid'] ?? 0),
            'revoked' => (int) ($latestResults['revoked'] ?? 0),
            'pending' => (int) ($latestResults['pending'] ?? 0),
            'unavailable' => (int) ($latestResults['unavailable'] ?? 0),
            'not_found' => (int) ($latestResults['not_found'] ?? 0),
        ];

        return [
            'documents_issued' => (clone $documents)->count(),
            'verification_attempts' => $attempts,
            'valid' => $valid,
            'invalid' => $invalid,
            'revoked' => $revoked,
            'other' => max(0, $attempts - $valid - $invalid - $revoked),
            'valid_rate' => $attempts === 0 ? 0.0 : round(($valid / $attempts) * 100, 1),
            'documents_revoked' => IssuedDocument::query()
                ->whereNotNull('revoked_at')
                ->whereBetween('revoked_at', [
                    $this->from($filters)->startOfDay(),
                    $this->to($filters)->endOfDay(),
                ])
                ->whereHas(
                    'serviceRequest',
                    fn (Builder $request) => $request->when(
                        $this->permitType($filters) !== null,
                        fn (Builder $builder) => $builder->where('service_type', $this->permitType($filters))
                    )
                )
                ->count(),
            'latest_results' => $latestResultCounts,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   staff: list<array{staff_name: string, processed: int, approved: int, rejected: int, average_processing_hours: ?float}>,
     *   unattributed: int
     * }
     */
    public function staffPerformance(array $filters): array
    {
        $staff = $this->requestQuery($filters)
            ->join('users as processors', 'processors.id', '=', 'service_requests.processed_by')
            ->whereNotNull('service_requests.processed_by')
            ->select('service_requests.processed_by', 'processors.name')
            ->selectRaw('COUNT(*) AS processed_count')
            ->selectRaw('SUM(CASE WHEN service_requests.status = ? THEN 1 ELSE 0 END) AS approved_count', [ServiceRequest::APPROVED])
            ->selectRaw('SUM(CASE WHEN service_requests.status = ? THEN 1 ELSE 0 END) AS rejected_count', [ServiceRequest::REJECTED])
            ->groupBy('service_requests.processed_by', 'processors.name')
            ->orderByDesc('processed_count')
            ->limit(10)
            ->get();
        $processorIds = $staff->pluck('processed_by');
        $processingTotals = [];

        if ($processorIds->isNotEmpty()) {
            foreach (
                $this->requestQuery($filters)
                    ->whereIn('processed_by', $processorIds)
                    ->whereIn('status', [ServiceRequest::APPROVED, ServiceRequest::REJECTED])
                    ->whereNotNull('created_at')
                    ->whereNotNull('processed_at')
                    ->select(['processed_by', 'created_at', 'processed_at'])
                    ->cursor() as $request
            ) {
                $hours = $this->processingHours(
                    Carbon::parse($request->created_at),
                    Carbon::parse($request->processed_at)
                );

                if ($hours === null) {
                    continue;
                }

                $processorId = (int) $request->processed_by;
                $processingTotals[$processorId]['hours'] = ($processingTotals[$processorId]['hours'] ?? 0) + $hours;
                $processingTotals[$processorId]['count'] = ($processingTotals[$processorId]['count'] ?? 0) + 1;
            }
        }

        $staffRows = $staff->map(function ($row) use ($processingTotals): array {
            $processorTotals = $processingTotals[(int) $row->processed_by] ?? ['hours' => 0, 'count' => 0];

            return [
                'staff_name' => $row->name,
                'processed' => (int) $row->processed_count,
                'approved' => (int) $row->approved_count,
                'rejected' => (int) $row->rejected_count,
                'average_processing_hours' => $processorTotals['count'] === 0
                    ? null
                    : round($processorTotals['hours'] / $processorTotals['count'], 2),
            ];
        })->all();
        $unattributed = $this->requestQuery($filters)
            ->whereIn('status', [ServiceRequest::PROCESSING, ServiceRequest::APPROVED, ServiceRequest::REJECTED])
            ->whereNull('processed_by')
            ->count();

        return ['staff' => $staffRows, 'unattributed' => $unattributed];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{my_workload: int, awaiting_action: int, identity_reviews_in_queue: int, oldest_pending_age_days: ?int}
     */
    public function staffAccess(User $staff): array
    {
        $oldestPending = ServiceRequest::query()
            ->where('status', ServiceRequest::PENDING)
            ->min('created_at');

        return [
            'my_workload' => ServiceRequest::query()
                ->where('processed_by', $staff->id)
                ->whereBetween('created_at', [today()->subDays(29)->startOfDay(), today()->endOfDay()])
                ->count(),
            'awaiting_action' => ServiceRequest::query()
                ->whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING])
                ->count(),
            'identity_reviews_in_queue' => IdentityVerification::query()
                ->where('status', IdentityVerification::PENDING)
                ->count(),
            'oldest_pending_age_days' => $oldestPending === null
                ? null
                : (int) floor(Carbon::parse($oldestPending)->diffInSeconds(now()) / 86400),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{recorded: int, pending: int, failed: int}
     */
    public function blockchainActivity(array $filters): array
    {
        $transactions = $this->blockchainTransactions($filters);

        return [
            'recorded' => $transactions['successful'],
            'pending' => $transactions['pending'],
            'failed' => $transactions['failed'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function requestQuery(array $filters): Builder
    {
        $query = ServiceRequest::query();

        if (isset($filters['from'], $filters['to'])) {
            $query->whereBetween('service_requests.created_at', [
                $this->from($filters)->startOfDay(),
                $this->to($filters)->endOfDay(),
            ]);
        }

        $permitType = $this->permitType($filters);

        if ($permitType !== null) {
            $query->where('service_requests.service_type', $permitType);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count: int, oldest_age_days: ?int}
     */
    public function backlogStats(int $olderThanDays, array $filters = []): array
    {
        $query = ServiceRequest::query()
            ->whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING])
            ->where('created_at', '<', now()->subDays($olderThanDays))
            ->when(
                $this->permitType($filters) !== null,
                fn (Builder $builder) => $builder->where('service_type', $this->permitType($filters))
            );
        $oldestCreatedAt = (clone $query)->min('created_at');

        return [
            'count' => (clone $query)->count(),
            'oldest_age_days' => $oldestCreatedAt === null
                ? null
                : (int) floor(Carbon::parse($oldestCreatedAt)->diffInSeconds(now()) / 86400),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documentTypeTitles(): array
    {
        return collect(config('barangay_services.services', []))
            ->mapWithKeys(fn (array $service) => [$service['type'] => $service['title']])
            ->all();
    }

    private function averageProcessingHours(array $filters): ?float
    {
        $sum = 0.0;
        $count = 0;

        foreach (
            $this->requestQuery($filters)
                ->whereIn('status', [ServiceRequest::APPROVED, ServiceRequest::REJECTED])
                ->whereNotNull('created_at')
                ->whereNotNull('processed_at')
                ->select(['created_at', 'processed_at'])
                ->cursor() as $request
        ) {
            $hours = $this->processingHours(
                Carbon::parse($request->created_at),
                Carbon::parse($request->processed_at)
            );

            if ($hours !== null) {
                $sum += $hours;
                $count++;
            }
        }

        return $count === 0 ? null : round($sum / $count, 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array{label: string, total: int, in_progress: int, approved: int, processing_hours_sum: float, processing_hours_count: int}>
     */
    private function emptyTrendBuckets(array $filters): array
    {
        $granularity = $filters['grouping'] ?? 'daily';
        $cursor = $this->from($filters)->startOfDay();
        $lastBucket = $this->to($filters)->startOfDay();
        $buckets = [];

        while ($cursor->lessThanOrEqualTo($lastBucket)) {
            $key = $this->periodKey($cursor, $granularity);
            $buckets[$key] = [
                'label' => match ($granularity) {
                    'monthly' => $cursor->format('M Y'),
                    'yearly' => $cursor->format('Y'),
                    default => $cursor->format('M j'),
                },
                'total' => 0,
                'in_progress' => 0,
                'approved' => 0,
                'processing_hours_sum' => 0.0,
                'processing_hours_count' => 0,
            ];

            $cursor = match ($granularity) {
                'monthly' => $cursor->addMonthNoOverflow()->startOfMonth(),
                'yearly' => $cursor->addYear()->startOfYear(),
                default => $cursor->addDay(),
            };
        }

        return $buckets;
    }

    private function periodKey(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'monthly' => $date->format('Y-m'),
            'yearly' => $date->format('Y'),
            default => $date->format('Y-m-d'),
        };
    }

    private function processingHours(Carbon $createdAt, Carbon $processedAt): ?float
    {
        if ($processedAt->lessThan($createdAt)) {
            return null;
        }

        return round($createdAt->diffInSeconds($processedAt) / 3600, 2);
    }

    private function filteredBlockchainRecords(array $filters): Builder
    {
        return BlockchainRecord::query()
            ->whereBetween('created_at', [
                $this->from($filters)->startOfDay(),
                $this->to($filters)->endOfDay(),
            ])
            ->when(
                $this->permitType($filters) !== null,
                fn (Builder $query) => $query->whereHas(
                    'issuedDocument.serviceRequest',
                    fn (Builder $request) => $request->where('service_type', $this->permitType($filters))
                )
            );
    }

    private function issuedDocuments(array $filters): Builder
    {
        return IssuedDocument::query()
            ->whereBetween('issued_at', [
                $this->from($filters)->startOfDay(),
                $this->to($filters)->endOfDay(),
            ])
            ->whereHas(
                'serviceRequest',
                fn (Builder $request) => $request->when(
                    $this->permitType($filters) !== null,
                    fn (Builder $builder) => $builder->where('service_type', $this->permitType($filters))
                )
            );
    }

    private function permitType(array $filters): ?string
    {
        $permitType = $filters['permit_type'] ?? $filters['document_type'] ?? null;

        return filled($permitType) ? (string) $permitType : null;
    }

    private function truncateHash(?string $hash): ?string
    {
        if (blank($hash)) {
            return null;
        }

        return strlen($hash) <= 24
            ? $hash
            : substr($hash, 0, 12).'...'.substr($hash, -8);
    }

    private function from(array $filters): Carbon
    {
        return Carbon::parse($filters['from']);
    }

    private function to(array $filters): Carbon
    {
        return Carbon::parse($filters['to']);
    }
}
