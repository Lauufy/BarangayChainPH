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

/** Generates, stores, and versions issued certificate PDFs. */
class DocumentIssuer
{
    private const DISK = 'supabase';

    /** Approves an open request and issues version 1. Returns the existing document if already issued. */
    public function issue(ServiceRequest $serviceRequest): IssuedDocument
    {
        return $this->locked($serviceRequest, function (ServiceRequest $application, ?string &$uploadedPath) {
            if ($existing = $this->activeDocument($application)) {
                return $existing;
            }

            if (! in_array($application->status, [ServiceRequest::PENDING, ServiceRequest::PROCESSING], true)) {
                throw new RuntimeException('Only open applications can be issued.');
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

    /** Issues a new version of an approved request's document and revokes the old one. */
    public function reissue(ServiceRequest $serviceRequest): IssuedDocument
    {
        return $this->locked($serviceRequest, function (ServiceRequest $application, ?string &$uploadedPath) {
            if ($application->status !== ServiceRequest::APPROVED) {
                throw new RuntimeException('Only approved applications can be reissued.');
            }

            $previous = $this->activeDocument($application);

            if (! $previous) {
                throw new RuntimeException('No active document is available for reissuance.');
            }

            return $this->replaceDocument($application, $previous, $uploadedPath);
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

    /** Latest non-revoked document, locked for update. */
    private function activeDocument(ServiceRequest $application): ?IssuedDocument
    {
        return $application->issuedDocuments()
            ->whereNull('revoked_at')
            ->lockForUpdate()
            ->latest('version')
            ->first();
    }

    /** Issues the next version of a document and revokes the previous one. */
    private function replaceDocument(
        ServiceRequest $application,
        IssuedDocument $previous,
        ?string &$uploadedPath
    ): IssuedDocument {
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

        $pdfContent = Pdf::loadView('permits.pdf', [
            'application' => $application,
            'document' => $document,
            'documentTitle' => $application->serviceTitle(),
            'issuedAt' => $issuedAt,
            'qrCode' => $this->qrCodeDataUri($documentUuid),
        ])->output();

        // Hash is checked again before every print
        $document->file_hash = hash('sha256', $pdfContent);

        // Remember the path so the caller can delete it if the transaction fails
        $uploadedPath = $document->file_path;

        if (! Storage::disk(self::DISK)->put($uploadedPath, $pdfContent)) {
            throw new RuntimeException($storeError);
        }

        return $document;
    }

    /** QR code that links to the public verification page. */
    private function qrCodeDataUri(string $documentUuid): string
    {
        $verificationUrl = rtrim(config('app.url'), '/')
            .route('permits.verify', ['documentUuid' => $documentUuid], false);

        return (new Builder(writer: new PngWriter, data: $verificationUrl, size: 120, margin: 4))
            ->build()
            ->getDataUri();
    }
}