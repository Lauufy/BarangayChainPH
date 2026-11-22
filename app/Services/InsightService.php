<?php

namespace App\Services;

use App\Models\BlockchainRecord;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;

class InsightService
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{type: string, severity: string, message: string, link: ?string}>
     */
    public function insights(array $filters): array
    {
        $insights = array_filter([
            $this->backlogInsight($filters),
            ...$this->rejectionInsights($filters),
            $this->blockchainInsight($filters),
        ]);

        $severityOrder = ['critical' => 0, 'warning' => 1];
        usort(
            $insights,
            fn (array $first, array $second) => ($severityOrder[$first['severity']] ?? 2)
                <=> ($severityOrder[$second['severity']] ?? 2)
        );

        return array_slice(array_values($insights), 0, 3);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, severity: string, message: string, link: ?string}|null
     */
    private function backlogInsight(array $filters): ?array
    {
        $days = (int) config('reports.thresholds.backlog_days');
        $backlog = $this->reports->backlogStats($days, $filters);

        if ($backlog['count'] === 0) {
            return null;
        }

        $requestLabel = $backlog['count'] === 1 ? 'request' : 'requests';
        $verb = $backlog['count'] === 1 ? 'is' : 'are';

        return [
            'type' => 'backlog',
            'severity' => 'warning',
            'message' => "{$backlog['count']} {$requestLabel} awaiting action {$verb} over {$days} days old; oldest: {$backlog['oldest_age_days']} days.",
            'link' => route('admin.permits.index'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{type: string, severity: string, message: string, link: ?string}>
     */
    private function rejectionInsights(array $filters): array
    {
        $totalQuery = $this->reports->requestQuery($filters);
        $totalCounts = (clone $totalQuery)
            ->selectRaw('service_type, COUNT(*) AS aggregate')
            ->groupBy('service_type')
            ->pluck('aggregate', 'service_type');
        $rejectedCounts = (clone $totalQuery)
            ->where('status', ServiceRequest::REJECTED)
            ->selectRaw('service_type, COUNT(*) AS aggregate')
            ->groupBy('service_type')
            ->pluck('aggregate', 'service_type');
        $minimum = (int) config('reports.thresholds.rejection_minimum_requests');
        $threshold = (float) config('reports.thresholds.rejection_rate_percent');
        $titles = $this->reports->documentTypeTitles();
        $insights = [];

        foreach ($totalCounts as $type => $total) {
            $rejected = (int) ($rejectedCounts[$type] ?? 0);

            if ((int) $total < $minimum) {
                continue;
            }

            $rate = ($rejected / (int) $total) * 100;

            if ($rate <= $threshold) {
                continue;
            }

            $insights[] = [
                'type' => 'high_rejection_rate',
                'severity' => 'warning',
                'message' => ($titles[$type] ?? $type).': '.(int) round($rate).'% rejected ('.$rejected.'/'.$total.').',
                'link' => route('admin.permits.index'),
            ];
        }

        return $insights;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, severity: string, message: string, link: ?string}|null
     */
    private function blockchainInsight(array $filters): ?array
    {
        $pendingMinutes = (int) config('reports.thresholds.blockchain_pending_minutes');
        $type = $filters['permit_type'] ?? $filters['document_type'] ?? null;
        $records = BlockchainRecord::query()
            ->whereHas('issuedDocument.serviceRequest', fn (Builder $query) => $query
                ->when($type, fn (Builder $builder) => $builder->where('service_type', $type)));
        $failed = (clone $records)
            ->whereNotNull('error')
            ->whereNull('revoked_at')
            ->count();
        $pending = (clone $records)
            ->whereNull('recorded_at')
            ->whereNull('error')
            ->whereNull('revoked_at')
            ->where('created_at', '<', now()->subMinutes($pendingMinutes))
            ->count();

        if ($failed + $pending === 0) {
            return null;
        }

        return [
            'type' => 'blockchain_health',
            'severity' => 'critical',
            'message' => "Blockchain: {$failed} failed, {$pending} pending over {$pendingMinutes} minutes.",
            'link' => route('admin.blockchain-ledger.index'),
        ];
    }
}
