<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UpdatesTwoFactor;
use App\Jobs\RecordPermitOnBlockchain;
use App\Jobs\RevokePermitOnBlockchain;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestDocument;
use App\Notifications\ServiceRequestApproved;
use App\Notifications\ServiceRequestRejected;
use App\Services\AuditLogger;
use App\Services\DocumentIssuer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PermitController extends Controller
{
    use UpdatesTwoFactor;

    public function index(): View
    {
        $requests = ServiceRequest::with('user')->latest()->paginate(10);

        // One query for all status counts
        $byStatus = ServiceRequest::selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $requestCounts = [
            'total' => $byStatus->sum(),
            'pending' => $byStatus[ServiceRequest::PENDING] ?? 0,
            'processing' => $byStatus[ServiceRequest::PROCESSING] ?? 0,
            'completed' => $byStatus[ServiceRequest::APPROVED] ?? 0,
        ];

        return view('admin.permits.index', compact('requests', 'requestCounts'));
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        // The first time staff open a pending request, it becomes "processing"
        $serviceRequest = $this->markProcessing($serviceRequest);

        return view('admin.permits.review', [
            'application' => $serviceRequest->load(['user', 'issuedDocuments.blockchainRecord', 'documents']),
        ]);
    }

    /** Streams a citizen's uploaded requirement from private storage. Never a public link. */
    public function document(ServiceRequest $serviceRequest, ServiceRequestDocument $document): StreamedResponse
    {
        abort_unless($document->service_request_id === $serviceRequest->id, 404);

        $disk = Storage::disk($document->disk);

        abort_unless($disk->exists($document->path), 404);

        app(AuditLogger::class)->log(
            'requirement_document_viewed',
            $serviceRequest,
            note: "Viewed {$document->label} ({$document->original_name})."
        );

        $stream = $disk->readStream($document->path);

        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', $document->original_name),
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Request $request, ServiceRequest $serviceRequest, DocumentIssuer $documentIssuer): RedirectResponse
    {
        $expiresAt = $this->validatedExpiry($request);

        try {
            $document = $documentIssuer->issue($serviceRequest, $expiresAt, $request->user()->id);
        } catch (Throwable $e) {
            report($e);

            return $this->back(
                $serviceRequest,
                'error',
                'The application could not be approved because its PDF document could not be generated or stored. Please try again.'
            );
        }

        // A repeat click returns the existing document, so only notify the first time
        if ($document->wasRecentlyCreated) {
            $this->recordOnBlockchain($document);

            $approved = $serviceRequest->fresh(['user', 'ticket']);

            $this->notify($approved, new ServiceRequestApproved($approved));

            $approved->ticket?->postSystemMessage(
                'Your request was approved. Your authenticated PDF is now available on the Documents page.',
                close: true
            );
        }

        return $this->back($serviceRequest, 'success', 'The application was approved and its authenticated PDF was generated successfully.');
    }

    public function reissue(Request $request, ServiceRequest $serviceRequest, DocumentIssuer $documentIssuer): RedirectResponse
    {
        $expiresAt = $this->validatedExpiry($request);

        try {
            $document = $documentIssuer->reissue($serviceRequest, $expiresAt);
        } catch (Throwable $e) {
            report($e);

            return $this->back(
                $serviceRequest,
                'error',
                'The document could not be reissued. The existing document remains unchanged. Please try again.'
            );
        }

        $previousDocument = IssuedDocument::query()
            ->where('service_request_id', $document->service_request_id)
            ->where('version', $document->version - 1)
            ->firstOrFail();

        $this->revokeOnBlockchain($previousDocument);
        $this->recordOnBlockchain($document);

        return $this->back($serviceRequest, 'success', "Document reissued successfully. New document version: {$document->version}.");
    }

    // Tries again to record a permit on the blockchain (for when the first try failed)
    public function retryBlockchain(ServiceRequest $serviceRequest): RedirectResponse
    {
        // The current document that is not recorded yet, or needs its recovered hash retried
        $document = $serviceRequest->issuedDocuments()
            ->whereNull('revoked_at')
            ->where(function (Builder $query) {
                $query
                    ->whereDoesntHave('blockchainRecord', fn (Builder $record) => $record->whereNotNull('recorded_at'))
                    ->orWhereHas('blockchainRecord', fn (Builder $record) => $record
                        ->whereNotNull('recorded_at')
                        ->whereNull('tx_hash'));
            })
            ->latest('version')
            ->first();

        if (! $document) {
            return $this->back($serviceRequest, 'error', 'This permit is already recorded on the blockchain, or has no active document.');
        }

        $this->recordOnBlockchain($document);

        // With a real queue the job has not run yet, so we cannot know the result
        if (config('queue.default') !== 'sync') {
            return $this->back($serviceRequest, 'success', 'The recording was queued. Refresh this page in a moment to see the result.');
        }

        $record = $document->fresh()->blockchainRecord;

        if (! $record?->recorded_at) {
            return $this->back($serviceRequest, 'error', 'The blockchain recording failed again. Please try again later.');
        }

        if (blank($record->tx_hash)) {
            return $this->back(
                $serviceRequest,
                'error',
                'The permit is valid on the blockchain, but its transaction hash could not be recovered. Please retry or backfill it.'
            );
        }

        return $this->back($serviceRequest, 'success', 'The permit is now recorded on the blockchain.');
    }

    public function reject(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $rejected = $this->decide($serviceRequest, [
            'status' => ServiceRequest::REJECTED,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        if (! $rejected) {
            return $this->back($serviceRequest, 'error', 'Only open applications can be rejected.');
        }

        $this->notify($rejected, new ServiceRequestRejected($rejected));

        $rejected->ticket?->postSystemMessage('Your request was rejected. Reason: '.$data['rejection_reason'], close: true);

        return $this->back($serviceRequest, 'success', 'The application was rejected.');
    }

    public function updatePrintLimit(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $data = $request->validate([
            'print_limit' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        if ($serviceRequest->status !== ServiceRequest::APPROVED) {
            return $this->back($serviceRequest, 'error', 'Only approved applications can have their print limit changed.');
        }

        if ($data['print_limit'] < $serviceRequest->print_count) {
            return redirect()
                ->route('admin.permits.show', $serviceRequest)
                ->withErrors([
                    'print_limit' => "The print limit cannot be lower than the {$serviceRequest->print_count} prints already used.",
                ]);
        }

        $serviceRequest->forceFill(['print_limit' => $data['print_limit']])->save();

        return $this->back($serviceRequest, 'success', 'The permit print limit was updated.');
    }

    /** Pending -> processing, once. Safe if two staff open it at the same time. */
    private function markProcessing(ServiceRequest $serviceRequest): ServiceRequest
    {
        if ($serviceRequest->status !== ServiceRequest::PENDING) {
            return $serviceRequest;
        }

        $updated = DB::transaction(function () use ($serviceRequest) {
            $locked = ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ServiceRequest::PENDING) {
                return null;
            }

            $locked->update([
                'status' => ServiceRequest::PROCESSING,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh();
        });

        // Someone else got there first
        if (! $updated) {
            return $serviceRequest->fresh();
        }

        try {
            $updated->ticket?->postSystemMessage('Your request is now being processed by the barangay.');
        } catch (Throwable $e) {
            report($e);
        }

        return $updated;
    }

    /** The "valid until" date chosen by staff: required, in the future, valid through the end of that day. */
    private function validatedExpiry(Request $request): Carbon
    {
        $data = $request->validate([
            'expires_at' => [
                'required',
                'date',
                'after:today',
                'before_or_equal:'.now()->addYears(5)->toDateString(),
            ],
        ]);

        return Carbon::parse($data['expires_at'], config('app.timezone'))->endOfDay();
    }

    /** Locks the row and applies the update only while the request is open (pending or processing). */
    private function decide(ServiceRequest $serviceRequest, array $attributes): ?ServiceRequest
    {
        return DB::transaction(function () use ($serviceRequest, $attributes) {
            $locked = ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [ServiceRequest::PENDING, ServiceRequest::PROCESSING], true)) {
                return null;
            }

            $locked->update([
                ...$attributes,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    // A failed notification must not break the action
    private function notify(ServiceRequest $serviceRequest, Notification $notification): void
    {
        try {
            $serviceRequest->user->notify($notification);
        } catch (Throwable $e) {
            report($e);
        }
    }

    // The blockchain must never break an approval. The job saves its own error
    // on the permit, so here we only log the problem and carry on.
    private function recordOnBlockchain(IssuedDocument $document): void
    {
        try {
            RecordPermitOnBlockchain::dispatch($document->id)->afterCommit();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function revokeOnBlockchain(IssuedDocument $document): void
    {
        try {
            RevokePermitOnBlockchain::dispatch($document->id)->afterCommit();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function back(ServiceRequest $serviceRequest, string $type, string $message): RedirectResponse
    {
        return redirect()->route('admin.permits.show', $serviceRequest)->with($type, $message);
    }
}
