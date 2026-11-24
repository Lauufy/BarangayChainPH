<?php

namespace App\Services;

use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PermitVerification
{
    public function __construct(private BlockchainRegistry $registry, private PermitFields $fields) {}

    public function check(string $reference, UploadedFile $upload): array
    {
        $path = $upload->getRealPath();
        $hash = $path ? hash_file('sha256', $path) : false;

        $document = Str::isUuid($reference)
            ? IssuedDocument::with(['serviceRequest', 'blockchainRecord'])->where('document_uuid', strtolower($reference))->first()
            : $this->findByPermitNumber($reference, $hash);

        if (! $document?->serviceRequest) {
            return ['status' => 'invalid', 'reason' => 'No permit matches this reference.'];
        }

        if ($hash === false || ! hash_equals($document->file_hash, $hash)) {
            $mime = $upload->getMimeType();
            $differences = [];
            if ($path && $mime) {
                try {
                    $differences = $this->fields->differences(
                        $path,
                        $mime,
                        $document->canonical_fields ?? PermitFields::snapshot($document, $document->serviceRequest),
                    );
                } catch (Throwable) {
                    // Extraction is diagnostic. The byte mismatch already determines the result.
                }
            }

            return [
                'status' => 'invalid',
                'document' => $document,
                'reason' => 'This file does not match the issued PDF for this reference. Upload the original PDF to verify the permit.',
                'differences' => $differences,
            ];
        }

        $anchor = $document->blockchainRecord;
        if (! $anchor?->recorded_at || blank($anchor->contract)) {
            return ['status' => 'unavailable', 'document' => $document];
        }

        try {
            $chain = $this->registry->lookup(
                $document->document_uuid,
                $anchor->contract,
                $anchor->anchorNetwork(),
                $document->canonical_fields !== null,
            );
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'unavailable', 'document' => $document];
        }

        if (($chain['ok'] ?? false) !== true) {
            return ['status' => 'unavailable', 'document' => $document];
        }

        // Reload after the network request so a concurrent reissue cannot validate the old version.
        $current = $document->fresh(['serviceRequest', 'replacement']);
        if ($current === null) {
            return ['status' => 'invalid', 'document' => $document];
        }

        $revoked = $current->revoked_at !== null;
        if (
            ($chain['exists'] ?? false) !== true
            || ! hash_equals('0x'.$hash, strtolower((string) ($chain['fileHash'] ?? '')))
            || ! hash_equals($current->file_hash, $hash)
            || ! is_int($chain['revokedAt'] ?? null)
            || (($chain['revokedAt'] > 0) !== $revoked)
            || ($current->canonical_fields === null && ($chain['version'] ?? 0) !== 0)
            || ($current->canonical_fields !== null && (
                ($chain['version'] ?? null) !== $current->version
                || ! hash_equals('0x'.PermitFields::commitment($current->canonical_fields), (string) ($chain['fieldsHash'] ?? ''))
                || $current->canonical_fields['number'] !== $current->document_number
                || $current->canonical_fields['issued'] !== $current->issued_at->toDateString()
                || $current->canonical_fields['expires'] !== ($current->expires_at?->toDateString() ?? 'None')
            ))
        ) {
            Log::warning('Permit database and blockchain records disagree.', ['issued_document_id' => $current->id]);

            return ['status' => 'invalid', 'document' => $current, 'reason' => 'The permit records disagree.'];
        }

        if ($revoked) {
            if ($current->replacement === null) {
                $current->setRelation('replacement', $current->serviceRequest?->issuedDocuments()
                    ->where('version', $current->version + 1)->first());
            }

            return ['status' => 'revoked', 'document' => $current];
        }

        $application = $current->serviceRequest;
        if (
            $application?->status !== ServiceRequest::APPROVED
            || $application->permit_number !== $current->document_number
            || $application->issuedDocuments()->where('version', '>', $current->version)->exists()
        ) {
            return ['status' => 'invalid', 'document' => $current];
        }

        return ['status' => $current->isExpired() ? 'expired' : 'valid', 'document' => $current];
    }

    private function findByPermitNumber(string $reference, string|false $hash): ?IssuedDocument
    {
        $application = ServiceRequest::where('permit_number', strtoupper($reference))->first();
        if ($application === null) {
            return null;
        }

        $documents = $application->issuedDocuments()
            ->where('document_number', $application->permit_number)
            ->with(['serviceRequest', 'blockchainRecord'])
            ->latest('version');

        return ($hash === false ? null : (clone $documents)->where('file_hash', $hash)->first())
            ?? $documents->first();
    }
}
