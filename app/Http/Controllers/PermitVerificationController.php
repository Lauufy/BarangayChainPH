<?php

namespace App\Http\Controllers;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Services\BlockchainRegistry;
use App\Support\PermitStatusCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/** Verifies issued certificates and prepares the public verification page. */
class PermitVerificationController extends Controller
{
    private const HIDE_INDIGENCY_TITLE = true;

    public function show(string $documentUuid): View
    {
        $document = IssuedDocument::with(['serviceRequest', 'blockchainRecord'])
            ->where('document_uuid', $documentUuid)
            ->first();

        $application = $document?->serviceRequest;
        $status = $this->cachedStatus($document, $application);

        // Only a valid document shows holder details.
        $isValid = $status === 'valid';

        return view('permits.verify', [
            'status' => $status,
            'documentTitle' => $this->documentTitle($application),
            'maskedApplicantName' => $isValid ? $this->maskApplicantName($application->applicant_name) : null,
            'referenceNumber' => $isValid ? $application->reference_number : null,
            'issuedAt' => $document?->issued_at,
            'revokedAt' => $document?->revoked_at,
            'expiresAt' => $document?->expires_at,
            'barangayName' => config('app.barangay_name', 'Barangay San Luis'),
        ]);
    }

    // A valid permit is checked at most once a minute to avoid slow blockchain calls.
    private function cachedStatus(?IssuedDocument $document, ?ServiceRequest $application): string
    {
        if ($document === null) {
            return $this->resolveStatus($document, $application);
        }

        $key = PermitStatusCache::keyFor($document->document_uuid);

        if (! $document->isValid()) {
            PermitStatusCache::forget($document->document_uuid);

            return 'revoked';
        }

        $status = Cache::get($key);

        if ($status !== 'valid') {
            Cache::forget($key);
            $status = null;
        }

        if ($status === null) {
            $status = $this->resolveStatus($document, $application);

            if ($status === 'valid') {
                $ttl = 60;

                if ($document->expires_at) {
                    $ttl = max(1, min(60, (int) now()->diffInSeconds($document->expires_at, false)));
                }

                Cache::put($key, $status, $ttl);
            }
        }

        return $status;
    }

    private function resolveStatus(?IssuedDocument $document, ?ServiceRequest $application): string
    {
        if ($document === null) {
            return 'not_found';
        }

        if (! $document->isValid()) {
            return 'revoked';
        }

        if ($document->isExpired()) {
            return 'expired';
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

            // Hash of the PDF as it exists right now
            $actualHash = hash('sha256', $disk->get($document->file_path));

            // Old check: file against the hash in our own database
            if (! hash_equals($document->file_hash, $actualHash)) {
                return 'invalid';
            }
        } catch (Throwable $e) {
            report($e);

            return 'unavailable';
        }

        // A permit is only valid when the blockchain confirms it.
        // Until it is recorded, the page shows "pending" and never "valid".
        if (! $document->blockchainRecord?->recorded_at) {
            return 'pending';
        }

        try {
            $result = app(BlockchainRegistry::class)->verify(
                $document->document_uuid,
                $actualHash,
                $document->blockchainRecord?->contract
            );
        } catch (Throwable $e) {
            report($e);

            return 'unavailable';
        }

        if (! ($result['ok'] ?? false)) {
            report(new \RuntimeException('Blockchain verify failed: '.($result['error'] ?? 'unknown')));

            return 'unavailable';
        }

        return match ($result['status'] ?? null) {
            'valid' => 'valid',
            'revoked' => 'revoked',
            'mismatch', 'not_found' => 'invalid',
            default => 'unavailable',
        };
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
            ServiceRequest::BUSINESS_CLEARANCE => 'Barangay Business Clearance',
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
