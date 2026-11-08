<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Lists a citizen's applications and issued documents, and handles printing and cancelling. */
class ServiceRequestController extends Controller
{
    /** Requests that are not yet approved. */
    public function applications(Request $request): View
    {
        $applications = $this->ownedRequests($request)
            ->with('ticket')
            ->where('status', '!=', ServiceRequest::APPROVED)
            ->paginate(10);

        return view('citizen.applications.index', compact('applications'));
    }

    /** Approved requests (the issued documents). */
    public function documents(Request $request): View
    {
        $documents = $this->ownedRequests($request)
            ->with('ticket')
            ->where('status', ServiceRequest::APPROVED)
            ->paginate(10);

        return view('citizen.documents.index', compact('documents'));
    }

    public function showApplication(Request $request, ServiceRequest $serviceRequest): View
    {
        $this->authorizeOwner($request, $serviceRequest);

        $ticket = $serviceRequest->ticket;

        return view('citizen.applications.review', [
            'application' => $serviceRequest,
            'documentTitle' => $serviceRequest->serviceTitle(),
            'ticket' => $ticket,
            'recentMessages' => $ticket?->messages()->latest()->take(3)->get()->reverse(),
        ]);
    }

    public function printApplication(Request $request, ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        $this->authorizeOwner($request, $serviceRequest);

        $printData = $this->recordPrint($serviceRequest);

        if ($printData === null) {
            return back()->with(
                'error',
                'This certificate cannot be printed because its stored PDF or QR code is unavailable. Please contact the barangay office for assistance.'
            );
        }

        return response($printData['fileContents'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$printData['document']->document_number}.pdf\"",
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function cancelApplication(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorizeOwner($request, $serviceRequest);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $cancelled = DB::transaction(function () use ($serviceRequest, $data) {
            $locked = $this->lock($serviceRequest);

            if (! $locked->isCancellable()) {
                return false;
            }

            $locked->update([
                'status' => ServiceRequest::CANCELLED,
                'cancellation_reason' => $data['cancellation_reason'],
                'cancelled_at' => now(),
                'permit_number' => null,
                'rejection_reason' => null,
            ]);

            $locked->ticket?->postSystemMessage('This request was canceled.', close: true);

            return true;
        });

        $redirect = redirect()->route('citizen.applications.show', $serviceRequest);

        return $cancelled
            ? $redirect->with('success', 'Your application was canceled successfully.')
            : $redirect->withErrors([
                'cancellation_reason' => 'This application can only be canceled while pending and within two hours of submission.',
            ]);
    }

    // Helpers

    private function ownedRequests(Request $request): Builder
    {
        return ServiceRequest::query()->whereBelongsTo($request->user())->latest();
    }

    private function authorizeOwner(Request $request, ServiceRequest $serviceRequest): void
    {
        abort_unless($serviceRequest->user_id === $request->user()->id, 404);
    }

    private function lock(ServiceRequest $serviceRequest): ServiceRequest
    {
        return ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * Locks the request, checks the printable document and counts the print.
     *
     * @return array{document: IssuedDocument, fileContents: string}|null
     */
    private function recordPrint(ServiceRequest $serviceRequest): ?array
    {
        return DB::transaction(function () use ($serviceRequest) {
            $locked = $this->lock($serviceRequest);
            $printData = $this->printableDocument($locked);

            if ($printData === null) {
                app(AuditLogger::class)->log(
                    'document_print_blocked',
                    $locked,
                    note: 'The stored certificate PDF or QR code is unavailable.'
                );

                return null;
            }

            $locked->increment('print_count');

            return $printData;
        });
    }

    /**
     * Returns the latest active PDF after checking status, print limit, file and hash.
     *
     * @return array{document: IssuedDocument, fileContents: string}|null
     */
    private function printableDocument(ServiceRequest $serviceRequest): ?array
    {
        abort_unless($serviceRequest->status === ServiceRequest::APPROVED, 404);
        abort_unless($serviceRequest->canPrint(), 403, 'This permit has reached its print limit.');

        $document = $serviceRequest->issuedDocuments()
            ->whereNull('revoked_at')
            ->latest('version')
            ->first();

        if (
            ! $document
            || $document->qr_code_generated_at === null
            || blank($document->file_path)
            || ! Storage::disk($document->disk)->exists($document->file_path)
        ) {
            return null;
        }

        $fileContents = Storage::disk($document->disk)->get($document->file_path);

        // The stored file must match the hash saved when it was issued
        abort_unless(
            filled($document->file_hash) && hash_equals($document->file_hash, hash('sha256', $fileContents)),
            409,
            'The document failed its integrity check. Please contact the barangay office.'
        );

        return [
            'document' => $document,
            'fileContents' => $fileContents,
        ];
    }
}