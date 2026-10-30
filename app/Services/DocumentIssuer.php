<?php

namespace App\Services;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DocumentIssuer
{
    private const DISK = 'supabase';

    public function issue(ServiceRequest $serviceRequest): IssuedDocument
    {
        return $this->locked($serviceRequest, function (ServiceRequest $application, ?string &$uploadedPath) {
            $existing = $application->issuedDocuments()
                ->whereNull('revoked_at')
                ->latest('version')
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($application->status !== ServiceRequest::PENDING) {
                throw new RuntimeException('Only pending applications can be issued.');
            }

            $documentNumber = $application->permit_number ?? ServiceRequest::generatePermitNumber();

            $document = $this->createDocument(
                $application,
                $documentNumber,
                1,
                $uploadedPath,
                'The PDF could not be stored.'
            );

            $application->update([
                'status' => ServiceRequest::APPROVED,
                'permit_number' => $documentNumber,
                'rejection_reason' => null,
            ]);

            $application->issuedDocuments()->save($document);

            return $document;
        });
    }

    public function ensureQrCode(ServiceRequest $serviceRequest): ?IssuedDocument
    {
        return DB::transaction(function () use ($serviceRequest) {
            $application = ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();

            if (! $application->canPrint()) {
                return null;
            }

            $document = $application->issuedDocuments()
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->latest('version')
                ->first();

            if (! $document || $document->qr_code_generated_at !== null) {
                return $document;
            }

            return $this->reissue($application);
        });
    }

    public function reissue(ServiceRequest $serviceRequest): IssuedDocument
    {
        return $this->locked($serviceRequest, function (ServiceRequest $application, ?string &$uploadedPath) {
            if ($application->status !== ServiceRequest::APPROVED) {
                throw new RuntimeException('Only approved applications can be reissued.');
            }

            /** @var IssuedDocument|null $previous */
            $previous = $application->issuedDocuments()
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->latest('version')
                ->first();

            if (! $previous) {
                throw new RuntimeException('No active document is available for reissuance.');
            }

            $document = $this->createDocument(
                $application,
                $previous->document_number,
                $previous->version + 1,
                $uploadedPath,
                'The replacement PDF could not be stored.'
            );

            $previous->update(['revoked_at' => $document->issued_at]);

            $application->issuedDocuments()->save($document);

            return $document;
        });
    }

    /**
     * Runs the callback in a transaction on the locked request.
     * If anything fails, the PDF uploaded by the callback is deleted.
     */
    private function locked(ServiceRequest $serviceRequest, callable $callback): IssuedDocument
    {
        $uploadedPath = null;

        try {
            return DB::transaction(function () use ($serviceRequest, $callback, &$uploadedPath) {
                $application = ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();

                return $callback($application, $uploadedPath);
            });
        } catch (Throwable $e) {
            if ($uploadedPath !== null) {
                try {
                    Storage::disk(self::DISK)->delete($uploadedPath);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $e;
        }
    }

    /** Builds the document record, renders the PDF and stores it. Does not save the record. */
    private function createDocument(
        ServiceRequest $application,
        string $documentNumber,
        int $version,
        ?string &$uploadedPath,
        string $storeError
    ): IssuedDocument {
        $issuedAt = now();
        $documentUuid = (string) Str::uuid();

        $document = new IssuedDocument([
            'document_uuid' => $documentUuid,
            'document_number' => $documentNumber,
            'version' => $version,
            'disk' => self::DISK,
            'file_path' => "issued-documents/{$documentUuid}.pdf",
            'issued_at' => $issuedAt,
            'qr_code_generated_at' => $issuedAt,
        ]);

        $verificationUrl = rtrim(config('app.url'), '/')
            .route('permits.verify', ['documentUuid' => $documentUuid], false);

        $qrCode = (new Builder(writer: new PngWriter, data: $verificationUrl, size: 120, margin: 4))
            ->build()
            ->getDataUri();

        logger()->info('QR code generated', [
            'format' => 'PNG',
            'data_uri_prefix' => substr($qrCode, 0, 30),
            'data_uri_length' => strlen($qrCode),
        ]);

        $pdfContent = Pdf::loadView('permits.pdf', [
            'application' => $application,
            'document' => $document,
            'documentTitle' => match ($application->service_type) {
                ServiceRequest::BARANGAY_CLEARANCE => 'Barangay Clearance',
                ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of Residency',
                ServiceRequest::CERTIFICATE_OF_INDIGENCY => 'Certificate of Indigency',
                default => 'Barangay Document',
            },
            'issuedAt' => $issuedAt,
            'qrCode' => $qrCode,
        ])->output();

        $document->file_hash = hash('sha256', $pdfContent);

        $uploadedPath = $document->file_path;

        if (! Storage::disk(self::DISK)->put($uploadedPath, $pdfContent)) {
            throw new RuntimeException($storeError);
        }

        return $document;
    }
}