<?php

namespace App\Http\Controllers;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/** Verifies issued certificates and prepares the public verification page. */
class PermitVerificationController extends Controller
{
    // Set to true once the office decides the public page must not reveal
    // that someone holds a Certificate of Indigency (open question #9).
    private const HIDE_INDIGENCY_TITLE = false;

    public function show(string $documentUuid): View
    {
        $document = IssuedDocument::with('serviceRequest')
            ->where('document_uuid', $documentUuid)
            ->first();

        $application = $document?->serviceRequest;
        $status = $this->resolveStatus($document, $application);

        // Only a valid document shows holder details.
        $isValid = $status === 'valid';

        return view('permits.verify', [
            'status' => $status,
            'documentTitle' => $this->documentTitle($application),
            'maskedApplicantName' => $isValid ? $this->maskApplicantName($application->applicant_name) : null,
            'referenceNumber' => $isValid ? $application->reference_number : null,
            'issuedAt' => $document?->issued_at,
            'revokedAt' => $document?->revoked_at,
            'barangayName' => config('app.barangay_name', 'Barangay San Luis'),
        ]);
    }

    /**
     * One of: not_found, revoked, invalid, unavailable, valid.
     * Phase 1B adds "expired" (after the revoked check, from valid_until).
     * Phase 4 adds "mismatch" (database and chain disagree).
     */
    private function resolveStatus(?IssuedDocument $document, ?ServiceRequest $application): string
    {
        if ($document === null) {
            return 'not_found';
        }

        if (! $document->isValid()) {
            return 'revoked';
        }

        if (
            $application === null
            || $application->status !== ServiceRequest::APPROVED
            || blank($application->permit_number)
            || $application->permit_number !== $document->document_number
        ) {
            return 'invalid';
        }

        try {
            $disk = Storage::disk($document->disk);

            if (! $disk->exists($document->file_path)) {
                return 'invalid';
            }

            $matches = hash_equals(
                $document->file_hash,
                hash('sha256', $disk->get($document->file_path))
            );
        } catch (Throwable $e) {
            report($e);

            return 'unavailable';
        }

        return $matches ? 'valid' : 'invalid';
    }

    private function documentTitle(?ServiceRequest $application): string
    {
        if (self::HIDE_INDIGENCY_TITLE && $application?->service_type === ServiceRequest::CERTIFICATE_OF_INDIGENCY) {
            return 'Barangay Certificate';
        }

        return match ($application?->service_type) {
            ServiceRequest::BARANGAY_CLEARANCE => 'Barangay Clearance',
            ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of Residency',
            ServiceRequest::CERTIFICATE_OF_INDIGENCY => 'Certificate of Indigency',
            default => 'Barangay Document',
        };
    }

    private function maskApplicantName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === []) {
            return 'Applicant';
        }

        return implode(' ', array_map(
            fn (string $part): string => Str::substr($part, 0, 1).'***',
            $parts
        ));
    }
}
