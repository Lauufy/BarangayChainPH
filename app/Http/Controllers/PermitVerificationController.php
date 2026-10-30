<?php

namespace App\\Http\\Controllers;

use App\\Models\\ServiceRequest;
use Illuminate\\Support\\Str;
use Illuminate\\View\\View;

class PermitVerificationController extends Controller
{
    public function show(ServiceRequest $serviceRequest): View
    {
        $isGenuine = $serviceRequest->status === ServiceRequest::APPROVED
            && filled($serviceRequest->permit_number);

        $documentTitle = match ($serviceRequest->service_type) {
            ServiceRequest::BARANGAY_CLEARANCE => 'Barangay Clearance',
            ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of Residency',
            ServiceRequest::CERTIFICATE_OF_INDIGENCY => 'Certificate of Indigency',
            default => 'Barangay Document',
        };

        return view('permits.verify', [
            'isGenuine' => $isGenuine,
            'documentTitle' => $documentTitle,
            'maskedApplicantName' => $isGenuine
                ? $this->maskApplicantName($serviceRequest->applicant_name)
                : null,
            'permitNumber' => $isGenuine ? $serviceRequest->permit_number : null,
            'referenceNumber' => $isGenuine ? $serviceRequest->reference_number : null,
        ]);
    }

    private function maskApplicantName(string $name): string
    {
        $parts = preg_split('/\\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) < 2) {
            return Str::substr($parts[0] ?? '', 0, 1).'.';
        }

        return $parts[0].' '.Str::upper(Str::substr(end($parts), 0, 1)).'.';
    }
}
