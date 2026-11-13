<?php

namespace App\Services;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, pending: int, approved: int, rejected: int, average_processing_hours: ?float}
     */
    public function summary(array $filters): array
    {
        $counts = $this->requestQuery($filters)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts[ServiceRequest::PENDING] ?? 0)
                + (int) ($counts[ServiceRequest::PROCESSING] ?? 0),
            'approved' => (int) ($counts[ServiceRequest::APPROVED] ?? 0),
            'rejected' => (int) ($counts[ServiceRequest::REJECTED] ?? 0),
            'average_processing_hours' => $this->averageProcessingHours($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{type: string, label: string, value: int}>
     */
    public function byDocumentType(array $filters): array
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
     * @return array{granularity: string, labels: list<string>, values: list<int>}
     */
    public function byPeriod(array $filters): array
    {
        $start = $this->from($filters)->startOfDay();
        $end = $this->to($filters)->startOfDay();
        $daily = (int) $start->diffInDays($end) + 1 <= 31;
        $cursor = $daily ? $start->copy() : $start->copy()->startOfMonth();
        $lastBucket = $daily ? $end : $end->copy()->startOfMonth();
        $buckets = [];

        while ($cursor->lessThanOrEqualTo($lastBucket)) {
            $key = $daily ? $cursor->format('Y-m-d') : $cursor->format('Y-m');
            $buckets[$key] = [
                'label' => $daily ? $cursor->format('M j') : $cursor->format('M Y'),
                'value' => 0,
            ];
            $cursor = $daily ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        foreach ($this->requestQuery($filters)->select(['id', 'created_at'])->cursor() as $request) {
            $date = Carbon::parse($request->created_at);
            $key = $daily ? $date->format('Y-m-d') : $date->format('Y-m');
            $buckets[$key]['value']++;
        }

        return [
            'granularity' => $daily ? 'daily' : 'monthly',
            'labels' => array_column($buckets, 'label'),
            'values' => array_column($buckets, 'value'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{recorded: int, pending: int, failed: int}
     */
    public function blockchainActivity(array $filters): array
    {
        $documents = $this->filteredDocuments($filters);

        $failed = (clone $documents)
            ->whereNull('revoked_at')
            ->whereHas('blockchainRecord', fn (Builder $query) => $query->whereNotNull('error'))
            ->count();
        $recorded = (clone $documents)
            ->whereNull('revoked_at')
            ->whereHas('blockchainRecord', fn (Builder $query) => $query
                ->whereNotNull('recorded_at')
                ->whereNull('error'))
            ->count();
        $pending = (clone $documents)
            ->whereNull('revoked_at')
            ->where(function (Builder $query) {
                $query->whereDoesntHave('blockchainRecord')
                    ->orWhereHas('blockchainRecord', fn (Builder $record) => $record
                        ->whereNull('recorded_at')
                        ->whereNull('error'));
            })
            ->count();

        return [
            'recorded' => $recorded,
            'pending' => $pending,
            'failed' => $failed,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function requestQuery(array $filters): Builder
    {
        return $this->applyFilters(ServiceRequest::query(), $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{count: int, oldest_age_days: ?int}
     */
    public function backlogStats(int $olderThanDays, array $filters = []): array
    {
        $query = $this->openBacklogQuery($olderThanDays, $filters);
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

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['from'], $filters['to'])) {
            $query->whereBetween('created_at', [
                $this->from($filters)->startOfDay(),
                $this->to($filters)->endOfDay(),
            ]);
        }

        if (! empty($filters['document_type'])) {
            $query->where('service_type', $filters['document_type']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredDocuments(array $filters): Builder
    {
        return IssuedDocument::query()
            ->whereHas('serviceRequest', fn (Builder $query) => $this->applyFilters($query, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function openBacklogQuery(int $olderThanDays, array $filters): Builder
    {
        return ServiceRequest::query()
            ->whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING])
            ->where('created_at', '<', now()->subDays($olderThanDays))
            ->when(
                ! empty($filters['document_type']),
                fn (Builder $query) => $query->where('service_type', $filters['document_type'])
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function averageProcessingHours(array $filters): ?float
    {
        $hours = [];

        foreach (
            $this->requestQuery($filters)
                ->where('status', ServiceRequest::APPROVED)
                ->whereNotNull('approved_at')
                ->select(['created_at', 'approved_at'])
                ->cursor() as $request
        ) {
            $submittedAt = Carbon::parse($request->created_at);
            $approvedAt = Carbon::parse($request->approved_at);

            if ($approvedAt->lessThan($submittedAt)) {
                continue;
            }

            $hours[] = round($submittedAt->diffInSeconds($approvedAt) / 3600, 2);
        }

        return $hours === [] ? null : round(array_sum($hours) / count($hours), 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function from(array $filters): Carbon
    {
        return Carbon::parse($filters['from']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function to(array $filters): Carbon
    {
        return Carbon::parse($filters['to']);
    }
}
