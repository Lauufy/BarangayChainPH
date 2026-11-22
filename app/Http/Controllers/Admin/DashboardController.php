<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Seconds the shared dashboard numbers stay cached. */
    private const STATS_TTL = 60;

    /** Seconds a staff member's workload summary stays cached. */
    private const WORKLOAD_TTL = 30;

    public function index(): View
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $can = fn (string $permission): bool => $user->hasPermission($permission);
        $firstName = str($user->name)->before(' ');
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        // Shared numbers: the same for every admin/staff, so they are cached once for everyone.
        // Run `php artisan cache:clear` if you need to see new records immediately.
        $stats = Cache::remember('admin.dashboard.stats', self::STATS_TTL, function (): array {
            return [
                'citizens' => $this->cardStats(
                    User::query()->where('role', 'citizen')
                ),
                'permits' => $this->cardStats(
                    ServiceRequest::query()->whereIn('status', [
                        ServiceRequest::PENDING,
                        ServiceRequest::PROCESSING,
                    ])
                ),
                'verifications' => $this->cardStats(
                    IdentityVerification::query()->where('status', IdentityVerification::PENDING),
                    'COALESCE(submitted_at, created_at)'
                ),
                'verified' => $this->cardStats(
                    IdentityVerification::query()->where('status', IdentityVerification::VERIFIED)
                ),
                'participation' => $this->participation(),
                'requestStatus' => $this->requestStatus(),
                // Issued permits that the blockchain has not confirmed yet
                'unrecordedPermits' => IssuedDocument::query()
                    ->whereNull('revoked_at')
                    ->whereDoesntHave('blockchainRecord', fn ($query) => $query->whereNotNull('recorded_at'))
                    ->count(),
            ];
        });

        // Personal workload: depends on the user's permissions, so it is cached per user.
        $workload = $user->isStaff()
            ? Cache::remember(
                'admin.dashboard.workload.'.$user->getKey(),
                self::WORKLOAD_TTL,
                fn (): array => $this->workload($user)
            )
            : null;

        // Kept fresh on purpose (small query, always up to date).
        $recentPermits = ServiceRequest::with('user')->latest()->limit(5)->get();

        $shortcuts = collect([
            ['admin.users.index', 'User & Role Management', $isAdmin],
            ['admin.identity-verification.index', 'Identity Verification', $can('identity.review')],
            ['admin.permits.index', 'Permit Management', $can('permits.review')],
            ['admin.reports.index', 'Reports & Analytics', $can('reports.view')],
        ])->filter(fn (array $shortcut): bool => $shortcut[2]);

        return view('admin.dashboard.index', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'can' => $can,
            'firstName' => $firstName,
            'greeting' => $greeting,
            'shortcuts' => $shortcuts,
            'panel' => 'rounded-2xl border border-(--gray-200) bg-(--white) shadow-sm dark:border-white/10 dark:bg-(--gray-800)',
            'panelHead' => 'border-b border-(--gray-200) px-5 py-4 dark:border-white/10',
            'panelTitle' => 'text-base font-semibold text-(--gray-900) dark:text-(--gray-50)',
            'muted' => 'text-(--gray-500) dark:text-(--gray-400)',
            'ink' => 'text-(--gray-900) dark:text-(--gray-50)',
            'miniCard' => 'rounded-xl bg-(--gray-50) p-4 dark:bg-(--gray-900)',
            'miniTitle' => 'text-sm text-(--gray-600) dark:text-(--gray-300)',
            'miniNumber' => 'mt-1 text-2xl font-bold tabular-nums text-(--gray-900) dark:text-(--gray-50)',

            'totalCitizens' => $stats['citizens']['total'],
            'citizensLatest' => $stats['citizens']['latest'], // last registered citizen
            'citizensTrend' => $stats['citizens']['trend'],

            'pendingPermits' => $stats['permits']['total'],
            'permitsLatest' => $stats['permits']['latest'], // last submitted request
            'permitsTrend' => $stats['permits']['trend'],

            'pendingVerifications' => $stats['verifications']['total'],
            'verificationsLatest' => $stats['verifications']['latest'], // last submitted verification
            'verificationsTrend' => $stats['verifications']['trend'],

            'authenticatedDocuments' => $stats['verified']['total'],
            'verifiedLatest' => $stats['verified']['latest'], // swap to your verified date column if you have one
            'verifiedTrend' => $stats['verified']['trend'],

            'participation' => $stats['participation'],
            'participationTotal' => array_sum($stats['participation']['values']),
            'requestStatus' => $stats['requestStatus'],
            'requestStatusTotal' => array_sum($stats['requestStatus']),

            'recentPermits' => $recentPermits,
            'unrecordedPermits' => $stats['unrecordedPermits'],
            'workload' => $workload,
        ]);
    }

    /**
     * Review workload for a staff member (pending permits and identity checks).
     *
     * @return array<string, mixed>
     */
    private function workload(User $user): array
    {
        $canReviewPermits = $user->hasPermission('permits.review');
        $canReviewIdentity = $user->hasPermission('identity.review');

        $requestQuery = ServiceRequest::query()
            ->whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING]);
        $identityQuery = IdentityVerification::query()
            ->where('status', IdentityVerification::PENDING);

        $oldestRequest = $canReviewPermits
            ? (clone $requestQuery)->orderBy('created_at')->first(['reference_number', 'service_type', 'created_at'])
            : null;
        $oldestIdentity = $canReviewIdentity
            ? (clone $identityQuery)
                ->orderByRaw('COALESCE(submitted_at, created_at)')
                ->first(['created_at', 'submitted_at'])
            : null;

        $oldestCreatedAt = $oldestRequest?->created_at;
        $oldestLabel = $oldestRequest
            ? $oldestRequest->reference_number.' · '.$oldestRequest->serviceTitle()
            : null;
        $identitySubmittedAt = $oldestIdentity?->submitted_at ?? $oldestIdentity?->created_at;

        if ($identitySubmittedAt && (! $oldestCreatedAt || $identitySubmittedAt->lessThan($oldestCreatedAt))) {
            $oldestCreatedAt = $identitySubmittedAt;
            $oldestLabel = 'Identity review';
        }

        return [
            'can_review_permits' => $canReviewPermits,
            'can_review_identity' => $canReviewIdentity,
            'requests' => $canReviewPermits ? (clone $requestQuery)->count() : 0,
            'identity_reviews' => $canReviewIdentity ? (clone $identityQuery)->count() : 0,
            'oldest_item' => $oldestCreatedAt ? [
                'label' => $oldestLabel,
                'age_days' => (int) floor(Carbon::parse($oldestCreatedAt)->diffInSeconds(now()) / 86400),
            ] : null,
        ];
    }

    /**
     * Everything one stat card needs from a single query:
     * the total, the latest date, and the month-over-month trend.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return array{total: int, latest: ?string, trend: ?float}
     */
    private function cardStats(Builder $query, string $latest = 'created_at'): array
    {
        $startOfMonth = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthSamePoint = now()->subMonthNoOverflow();

        $row = (clone $query)->toBase()->selectRaw(
            "COUNT(*) as total,
             MAX({$latest}) as latest,
             SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as this_month,
             SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as last_month",
            [$startOfMonth, $lastMonthStart, $lastMonthSamePoint]
        )->first();

        $thisMonth = (int) ($row->this_month ?? 0);
        $lastMonth = (int) ($row->last_month ?? 0);

        return [
            'total' => (int) ($row->total ?? 0),
            // With no records the date is null; the stat card shows a temporary date for a 0 value.
            'latest' => $row->latest ?? null,
            // Null when last month has nothing to compare against (the card hides the trend).
            'trend' => $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : null,
        ];
    }

    /**
     * Permit requests per month for the last 12 months (bar chart).
     * The database does the grouping, so no records are loaded into PHP.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function participation(): array
    {
        $start = now()->subMonths(11)->startOfMonth();
        $monthExpression = $this->monthExpression();

        $byMonth = ServiceRequest::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("{$monthExpression} as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonths($i));

        return [
            'labels' => $months->map(fn ($month) => $month->format('M'))->all(),
            'values' => $months->map(fn ($month) => (int) ($byMonth[$month->format('Y-m')] ?? 0))->all(),
        ];
    }

    /**
     * All permit requests grouped for the donut (pending + processing = in progress).
     *
     * @return array{approved: int, in_progress: int, rejected: int, cancelled: int}
     */
    private function requestStatus(): array
    {
        $counts = ServiceRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'approved' => (int) ($counts['approved'] ?? 0),
            'in_progress' => (int) (($counts[ServiceRequest::PENDING] ?? 0) + ($counts[ServiceRequest::PROCESSING] ?? 0)),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
        ];
    }

    /**
     * SQL that turns created_at into "YYYY-MM" for the current database driver.
     */
    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m')", // MySQL / MariaDB
        };
    }
}
