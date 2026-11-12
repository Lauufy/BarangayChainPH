<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
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
        ]);
    }
}