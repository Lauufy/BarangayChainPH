<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PermitsExport;
use App\Http\Controllers\Concerns\UpdatesTwoFactor;
use App\Http\Controllers\Controller;
use App\Jobs\RecordPermitOnBlockchain;
use App\Jobs\RevokePermitOnBlockchain;
use App\Models\BlockchainRecord;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestDocument;
use App\Notifications\ServiceRequestApproved;
use App\Notifications\ServiceRequestRejected;
use App\Services\AuditLogger;
use App\Services\BlockchainRegistry;
use App\Services\DocumentIssuer;
use App\Services\PermitCorrectionFields;
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

    public function index(Request $request, PermitsExport $export): View
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 10;
        $requests = $export->query($request)->paginate($perPage)->appends($request->except('search'));

        // One query for all status counts and dates
        $byStatus = ServiceRequest::selectRaw('
            status,
            COUNT(*) AS total,
            MIN(created_at) AS oldest_submitted,
            MAX(created_at) AS latest_submitted,
            MAX(COALESCE(processed_at, updated_at)) AS latest_activity
        ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $requestCounts = [
            'total' => $byStatus->sum('total'),
            'pending' => ($byStatus[ServiceRequest::PENDING]->total ?? 0)
                + ($byStatus[ServiceRequest::PROCESSING]->total ?? 0),
            'processing' => $byStatus[ServiceRequest::PROCESSING]->total ?? 0,
            'completed' => $byStatus[ServiceRequest::APPROVED]->total ?? 0,
        ];

        $toDate = fn ($value) => $value ? Carbon::parse($value) : null;

        $requestLatest = [
            'total' => $toDate($byStatus->max('latest_submitted')),
            'pending' => $toDate($byStatus
                ->only([ServiceRequest::PENDING, ServiceRequest::PROCESSING])
                ->min('oldest_submitted')),
            'processing' => $toDate($byStatus[ServiceRequest::PROCESSING]->latest_activity ?? null),
            'completed' => $toDate($byStatus[ServiceRequest::APPROVED]->latest_activity ?? null),
        ];

        return view('admin.permits.index', compact('requests', 'requestCounts', 'requestLatest'));
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        // The first time staff open a pending request, it becomes "processing"
        $serviceRequest = $this->markProcessing($serviceRequest);
        $application = $serviceRequest->load(['user', 'issuedDocuments.blockchainRecord', 'documents', 'revisions' => fn ($query) => $query->latest('id')]);
        $status = $application->status;
        $isApproved = $status === ServiceRequest::APPROVED;
        $isOpen = in_array($status, [ServiceRequest::PENDING, ServiceRequest::PROCESSING], true);
        $activeDocuments = $application->issuedDocuments->whereNull('revoked_at');
        $currentRecord = $activeDocuments->sortByDesc('version')->first()?->blockchainRecord;
        [$statusLabel, $statusVariant] = [
            'pending' => ['Pending review', 'warning'],
            ServiceRequest::RESUBMISSION_REQUIRED => ['Resubmission Required', 'warning'],
            'processing' => ['Processing', 'info'],
            'approved' => ['Approved', 'success'],
            'rejected' => ['Rejected', 'danger'],
            'cancelled' => ['Cancelled', 'neutral'],
        ][$status] ?? [ucfirst($status), 'neutral'];

        $details = array_filter([
            'Full name' => $application->applicant_name,
            'Age' => $application->age,
            'Civil status' => $application->civil_status,
            'Contact number' => $application->contact_number,
            'Email' => $application->user->email,
            'Date submitted' => $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            'Permit number' => $application->permit_number,
            'Prints remaining' => $isApproved
                ? $application->printsRemaining().' of '.$application->effectivePrintLimit()
                : null,
        ], fn ($value) => filled($value));

        $fieldLabels = collect($application->serviceConfig()['fields'] ?? [])->pluck('label', 'key');
        $requestDetails = ['Purpose' => $application->purpose];

        foreach ($application->details ?? [] as $key => $value) {
            $requestDetails[$fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))] = $value;
        }

        $requestDetails['Cancellation reason'] = $application->cancellation_reason;
        $requestDetails = array_filter($requestDetails, fn ($value) => filled($value));

        return view('admin.permits.review', [
            'application' => $application,
            'status' => $status,
            'isApproved' => $isApproved,
            'isOpen' => $isOpen,
            'activeDocuments' => $activeDocuments,
            'currentRecord' => $currentRecord,
            'correctionFields' => PermitCorrectionFields::for($application),
            'statusLabel' => $statusLabel,
            'statusVariant' => $statusVariant,
            'details' => $details,
            'requestDetails' => $requestDetails,
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

    public function approve(
        Request $request,
        ServiceRequest $serviceRequest,
        DocumentIssuer $documentIssuer,
        BlockchainRegistry $registry
    ): RedirectResponse {
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
            $contractInfo = $registry->contractInfo();
            $this->recordOnBlockchain($document, ($contractInfo['paused'] ?? false) === true);

            $approved = $serviceRequest->fresh(['user', 'ticket']);

            $this->notify($approved, new ServiceRequestApproved($approved));

            $approved->ticket?->postSystemMessage(
                'Your request was approved. Your PDF will be available on the Documents page after blockchain confirmation.',
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
            app(AuditLogger::class)->log('contract_operation_failed', $serviceRequest, newValues: [
                'operation' => 'retry', 'network' => config('blockchain.network'), 'contract' => config('blockchain.contract'),
                'result' => 'No eligible document.',
            ]);

            return $this->back($serviceRequest, 'error', 'This permit is already recorded on the blockchain, or has no active document.');
        }

        app(AuditLogger::class)->log('permit_blockchain_retry', $document, newValues: [
            'network' => $document->blockchainRecord?->anchorNetwork() ?? config('blockchain.network'),
            'contract' => $document->blockchainRecord?->contract ?? config('blockchain.contract'),
            'transaction' => $document->blockchainRecord?->tx_hash, 'result' => 'requested',
        ]);
        $dispatched = $this->recordOnBlockchain($document);
        if (! $dispatched) {
            $record = $document->fresh()->blockchainRecord;
            app(AuditLogger::class)->log('contract_operation_failed', $document, newValues: [
                'operation' => 'retry', 'network' => $record?->anchorNetwork() ?? config('blockchain.network'),
                'contract' => $record?->contract ?? config('blockchain.contract'), 'transaction' => $record?->tx_hash,
                'result' => 'Dispatch or processing failed.',
            ]);

            return $this->back($serviceRequest, 'error', 'Recording could not be completed. Check the transaction status.');
        }

        // With a real queue the job has not run yet, so we cannot know the result
        if (config('queue.default') !== 'sync') {
            return $this->back($serviceRequest, 'registry_notice', 'Recording requested. Refresh this page to see the result.');
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

    // Record a pending or known paused result before queuing the blockchain write.
    private function recordOnBlockchain(IssuedDocument $document, bool $contractIsPaused = false): bool
    {
        try {
            BlockchainRecord::firstOrCreate(
                ['issued_document_id' => $document->id],
                [
                    'network' => config('blockchain.network'),
                    'contract' => config('blockchain.contract'),
                    'error' => $contractIsPaused ? 'Blockchain issue failed: ContractPaused' : null,
                ]
            );

        } catch (Throwable $e) {
            report($e);

            return false;
        }

        if ($contractIsPaused) {
            return false;
        }

        try {
            RecordPermitOnBlockchain::dispatch($document->id)->afterCommit();
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return true;
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
