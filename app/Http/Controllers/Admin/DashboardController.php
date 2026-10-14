<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
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
        ]);
    }
}
