<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $requests = ServiceRequest::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        return view('citizen.dashboard.index', [
            'recentRequests' => $requests,
        ]);
    }
}
