<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canReviewPermits = $user->hasPermission('permits.review');
        $canReviewIdentity = $user->hasPermission('identity.review');
        $workload = null;

        if ($user->isStaff()) {
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

            $workload = [
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

        // One query per card: the count and the "latest" date come from the same records.
        // With no records the date is null; the stat card shows a temporary date for a 0 value.
        $citizens = User::query()->where('role', 'citizen');
        $pendingPermits = ServiceRequest::query()->where('status', ServiceRequest::PENDING);
        $pendingVerifications = IdentityVerification::query()->where('status', IdentityVerification::PENDING);
        $verified = IdentityVerification::query()->where('status', IdentityVerification::VERIFIED);

        return view('admin.dashboard.index', [
            'totalCitizens' => (clone $citizens)->count(),
            'citizensLatest' => (clone $citizens)->max('created_at'), // last registered citizen
            'citizensTrend' => $this->monthTrend($citizens),

            'pendingPermits' => (clone $pendingPermits)->count(),
            'permitsLatest' => (clone $pendingPermits)->max('created_at'), // last submitted request
            'permitsTrend' => $this->monthTrend($pendingPermits),

            'pendingVerifications' => (clone $pendingVerifications)->count(),
            'verificationsLatest' => (clone $pendingVerifications)
                ->selectRaw('MAX(COALESCE(submitted_at, created_at)) as latest')
                ->value('latest'), // last submitted verification
            'verificationsTrend' => $this->monthTrend($pendingVerifications),

            'authenticatedDocuments' => (clone $verified)->count(),
            'verifiedLatest' => (clone $verified)->max('created_at'), // swap to your verified date column if you have one
            'verifiedTrend' => $this->monthTrend($verified),

            'recentPermits' => ServiceRequest::with('user')->latest()->limit(5)->get(),
            // Issued permits that the blockchain has not confirmed yet
            'unrecordedPermits' => IssuedDocument::query()
                ->whereNull('revoked_at')
                ->whereDoesntHave('blockchainRecord', fn ($query) => $query->whereNotNull('recorded_at'))
                ->count(),
            'workload' => $workload,
        ]);
    }

    /**
     * Percent change in new records: this month so far vs the same stretch of last month.
     * Returns null when last month has nothing to compare against (the card hides the trend).
     */
    private function monthTrend(Builder $query): ?float
    {
        $startOfMonth = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthSamePoint = now()->subMonthNoOverflow();

        $thisMonth = (clone $query)->where('created_at', '>=', $startOfMonth)->count();
        $lastMonth = (clone $query)->whereBetween('created_at', [$lastMonthStart, $lastMonthSamePoint])->count();

        return $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : null;
    }
}