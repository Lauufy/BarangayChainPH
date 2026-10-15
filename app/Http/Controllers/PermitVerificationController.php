<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\View\View;

class PermitVerificationController extends Controller
{
    public function show(ServiceRequest $serviceRequest): View
    {
        $isGenuine = $serviceRequest->status === ServiceRequest::APPROVED
            && filled($serviceRequest->permit_number);

        return view('permits.verify', [
            'application' => $serviceRequest,
            'isGenuine' => $isGenuine,
        ]);
    }
}
