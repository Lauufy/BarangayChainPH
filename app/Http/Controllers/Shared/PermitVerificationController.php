<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\VerificationLog;
use App\Services\PermitFields;
use App\Services\PermitVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class PermitVerificationController extends Controller
{
    public function legacy(string $documentUuid): RedirectResponse
    {
        return redirect()->route('permits.verify.form', ['ref' => $documentUuid]);
    }

    public function show(Request $request): Response
    {
        $reference = $request->query('ref');
        $reference = is_string($reference) ? substr($reference, 0, 64) : '';

        // Typed permit numbers, blank visits and ?upload=1 get the upload form
        if (! Str::isUuid($reference) || $request->boolean('upload')) {
            return $this->uploadPage($reference);
        }

        $document = IssuedDocument::with(['serviceRequest', 'replacement'])
            ->where('document_uuid', strtolower($reference))
            ->first();
        $application = $document?->serviceRequest;

        $result = ['status' => 'invalid', 'reason' => 'No permit matches this QR code.', 'qr' => true];

        if ($application) {
            $result['document'] = $document;
            $result['reason'] = null;

            if ($document->revoked_at !== null) {
                $result['status'] = 'revoked';
            } elseif (
                $application->status !== ServiceRequest::APPROVED
                || $application->permit_number !== $document->document_number
                || $application->issuedDocuments()->where('version', '>', $document->version)->exists()
            ) {
                $result['reason'] = 'This permit is no longer current.';
            } else {
                $result['status'] = $document->isExpired() ? 'expired' : 'valid';
            }
        }

        return $this->resultPage($reference, $result);
    }

    public function verify(Request $request, PermitVerification $verification): Response
    {
        $validator = Validator::make($request->all(), [
            'ref' => ['required', 'string', 'max:64'],
            'permit' => ['required', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
        ], [
            'ref.required' => 'Enter the permit number printed on your document.',
            'permit.required' => 'Choose a permit file to upload.',
            'permit.mimetypes' => 'Choose a PDF, JPEG, or PNG file.',
            'permit.max' => 'Choose a file no larger than 10 MB.',
        ], [
            'ref' => 'permit number or QR reference',
            'permit' => 'permit file',
        ]);

        $reference = $request->input('ref');
        $reference = is_string($reference) ? substr($reference, 0, 64) : '';
        $invalidUpload = $validator->fails();
        $result = $invalidUpload
            ? ['status' => 'invalid']
            : $verification->check($reference, $request->file('permit'));

        try {
            VerificationLog::create([
                'service_request_id' => ($result['document'] ?? null)?->service_request_id,
                'result' => $result['status'] === 'expired' ? 'invalid' : $result['status'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($invalidUpload) {
            return $this->uploadPage($reference, $validator->errors()->getMessages(), 422);
        }

        return $this->resultPage($reference, $result);
    }

    private function uploadPage(string $reference, array $fieldErrors = [], int $httpStatus = 200): Response
    {
        return response()->view('permits.upload', [
            'reference' => $reference,
            'fieldErrors' => $fieldErrors,
            'barangayName' => config('app.barangay_name', 'Barangay San Luis'),
        ], $httpStatus, ['Cache-Control' => 'no-store, private']);
    }

    private function resultPage(string $reference, array $result): Response
    {
        $status = $result['status'] ?? null;
        $document = $result['document'] ?? null;

        return response()->view('permits.result', [
            'reference' => $reference,
            'status' => $status,
            'document' => $document,
            'details' => $document && ($status === 'valid' || ($result['qr'] ?? false)) ? $this->details($document) : [],
            'reason' => $result['reason'] ?? null,
            'qr' => $result['qr'] ?? false,
            'differences' => $result['differences'] ?? [],
            'barangayName' => config('app.barangay_name', 'Barangay San Luis'),
        ], 200, ['Cache-Control' => 'no-store, private']);
    }

    private function details(IssuedDocument $document): array
    {
        $fields = $document->canonical_fields ?? PermitFields::snapshot($document, $document->serviceRequest);
        $parts = preg_split('/\s+/u', $fields['holder'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $holder = implode(' ', array_map(
            fn(string $part, int $i): string => $i === 0 ? $part : Str::substr($part, 0, 1) . '***',
            $parts,
            array_keys($parts)
        ));

        return [
            'Document type' => $this->documentTitle($document->serviceRequest, $fields['type']),
            'Permit number' => $fields['number'],
            'Reference number' => $document->serviceRequest->reference_number,
            'Holder' => $holder ?: 'Applicant',
            'Issued' => $fields['issued'],
            'Expires' => $fields['expires'],
            'Issuing office' => $fields['issuer'],
            'Version' => $fields['version'],
        ];
    }

    private function documentTitle(ServiceRequest $application, ?string $issuedTitle = null): string
    {
        if (
            $application->service_type === ServiceRequest::CERTIFICATE_OF_INDIGENCY
            || ($issuedTitle !== null && Str::contains($issuedTitle, 'indigency', ignoreCase: true))
        ) {
            return 'Barangay Certificate';
        }

        return $issuedTitle ?? $application->serviceTitle();
    }
}
