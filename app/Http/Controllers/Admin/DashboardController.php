<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
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

        return view('admin.dashboard.index', [
            'totalCitizens' => User::query()
                ->where('role', 'citizen')
                ->count(),
            'pendingPermits' => ServiceRequest::query()
                ->where('status', ServiceRequest::PENDING)
                ->count(),
            'pendingVerifications' => IdentityVerification::query()
                ->where('status', IdentityVerification::PENDING)
                ->count(),
            'authenticatedDocuments' => IdentityVerification::query()
                ->where('status', IdentityVerification::VERIFIED)
                ->count(),
            'recentPermits' => ServiceRequest::with('user')->latest()->limit(5)->get(),
            // Issued permits that the blockchain has not confirmed yet
            'unrecordedPermits' => IssuedDocument::query()
                ->whereNull('revoked_at')
                ->whereDoesntHave('blockchainRecord', fn ($query) => $query->whereNotNull('recorded_at'))
                ->count(),
            'workload' => $workload,
        ]);
    }
}
