<?php

namespace App\Http\Controllers;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PermitVerificationController extends Controller
{
    public function show(string $documentUuid): View
    {
        $document = IssuedDocument::with('serviceRequest')
            ->where('document_uuid', $documentUuid)
            ->first();

        $application = $document?->serviceRequest;

        $isGenuine = $document !== null
            && $document->isValid()
            && $application !== null
            && $application->status === ServiceRequest::APPROVED
            && filled($application->permit_number)
            && $application->permit_number === $document->document_number
            && Storage::disk($document->disk)->exists($document->file_path)
            && hash_equals(
                $document->file_hash,
                hash('sha256', Storage::disk($document->disk)->get($document->file_path))
            );

        return view('permits.verify', [
            'isGenuine' => $isGenuine,
            'documentTitle' => match ($application?->service_type) {
                ServiceRequest::BARANGAY_CLEARANCE => 'Barangay Clearance',
                ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of Residency',
                ServiceRequest::CERTIFICATE_OF_INDIGENCY => 'Certificate of Indigency',
                default => 'Barangay Document',
            },
            'maskedApplicantName' => $isGenuine ? $this->maskApplicantName($application->applicant_name) : null,
            'permitNumber' => $isGenuine ? $document->document_number : null,
            'referenceNumber' => $isGenuine ? $application->reference_number : null,
        ]);
    }

    private function maskApplicantName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) < 2) {
            return Str::substr($parts[0] ?? '', 0, 1).'.';
        }

        return $parts[0].' '.Str::upper(Str::substr(end($parts), 0, 1)).'.';
    }
}