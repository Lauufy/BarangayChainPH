<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\IssuedDocument;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\CitizenSubmissionReceived;
use App\Services\DocumentIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    private const TYPES = [
        'indigency' => [
            'service' => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
            'title' => 'Certificate of Indigency',
            'route' => 'citizen.certificate-of-indigency',
            'heading' => 'Certificate of indigency',
            'description' => 'Ask the barangay about eligibility and requirements for this certificate.',
            'flag' => 'isIndigency',
        ],
        'clearance' => [
            'service' => ServiceRequest::BARANGAY_CLEARANCE,
            'title' => 'Barangay Clearance',
            'route' => 'citizen.barangay-clearance',
            'heading' => 'Barangay clearance',
            'description' => 'Request a clearance for employment, business, or other official purposes.',
            'flag' => 'isClearance',
        ],
        'residency' => [
            'service' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            'title' => 'Certificate of Residency',
            'route' => 'citizen.certificate-of-residency',
            'heading' => 'Certificate of residency',
            'description' => 'Request proof of residency within the barangay.',
            'flag' => 'isResidency',
        ],
    ];

    // Form pages

    public function indigency(Request $request): View|RedirectResponse
    {
        return $this->form($request, 'indigency');
    }

    public function clearance(Request $request): View|RedirectResponse
    {
        return $this->form($request, 'clearance');
    }

    public function residency(Request $request): View|RedirectResponse
    {
        return $this->form($request, 'residency');
    }

    // Preview

    public function previewIndigency(Request $request): View
    {
        return $this->previewRequest($request, 'indigency');
    }

    public function previewClearance(Request $request): View
    {
        return $this->previewRequest($request, 'clearance');
    }

    public function previewResidency(Request $request): View
    {
        return $this->previewRequest($request, 'residency');
    }

    // Modify

    public function modifyIndigency(Request $request): RedirectResponse
    {
        return $this->modifyRequest($request, 'indigency');
    }

    public function modifyClearance(Request $request): RedirectResponse
    {
        return $this->modifyRequest($request, 'clearance');
    }

    public function modifyResidency(Request $request): RedirectResponse
    {
        return $this->modifyRequest($request, 'residency');
    }

    // Proceed

    public function proceedIndigency(Request $request): RedirectResponse
    {
        return $this->proceedRequest($request, 'indigency');
    }

    public function proceedClearance(Request $request): RedirectResponse
    {
        return $this->proceedRequest($request, 'clearance');
    }

    public function proceedResidency(Request $request): RedirectResponse
    {
        return $this->proceedRequest($request, 'residency');
    }

    // Lists and details

    public function applications(Request $request): View
    {
        $applications = $this->ownedRequests($request)
            ->with('ticket')
            ->where('status', '!=', ServiceRequest::APPROVED)
            ->paginate(10);

        return view('citizen.applications', compact('applications'));
    }

    public function documents(Request $request): View
    {
        $documents = $this->ownedRequests($request)
            ->with('ticket')
            ->where('status', ServiceRequest::APPROVED)
            ->paginate(10);

        return view('citizen.documents', compact('documents'));
    }

    public function showApplication(Request $request, ServiceRequest $serviceRequest): View
    {
        $this->authorizeOwner($request, $serviceRequest);

        $ticket = $serviceRequest->ticket;

        return view('citizen.application-review', [
            'application' => $serviceRequest,
            'documentTitle' => $this->documentTitle($serviceRequest),
            'ticket' => $ticket,
            'recentMessages' => $ticket?->messages()->latest()->take(3)->get()->reverse(),
        ]);
    }

    public function printApplication(
        Request $request,
        ServiceRequest $serviceRequest,
        DocumentIssuer $documentIssuer
    ): Response {
        $this->authorizeOwner($request, $serviceRequest);

        $printData = $this->recordPrint($serviceRequest);

        if ($printData['requiresQrUpgrade']) {
            $documentIssuer->ensureQrCode($serviceRequest);

            $printData = $this->recordPrint($serviceRequest, afterUpgrade: true);
        }

        return response($printData['fileContents'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$printData['document']->document_number.'.pdf"',
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

            $cancelled = $locked->isCancellable() && $locked->update([
                'status' => ServiceRequest::CANCELLED,
                'cancellation_reason' => $data['cancellation_reason'],
                'cancelled_at' => now(),
                'permit_number' => null,
                'rejection_reason' => null,
            ]);

            if ($cancelled) {
                $locked->ticket?->postSystemMessage('This request was canceled.', close: true);
            }

            return $cancelled;
        });

        $redirect = redirect()->route('citizen.applications.show', $serviceRequest);

        return $cancelled
            ? $redirect->with('success', 'Your application was canceled successfully.')
            : $redirect->withErrors([
                'cancellation_reason' => 'This application can only be canceled while pending and within two hours of submission.',
            ]);
    }

    // Helpers

    private function form(Request $request, string $type): View|RedirectResponse
    {
        if ($this->pendingQuery($request, $type)->exists()) {
            return $this->pendingBlocked();
        }

        $info = self::TYPES[$type];

        return view('citizen.service-request', [
            'title' => $info['heading'],
            'description' => $info['description'],
            'profile' => $request->user()->citizenProfile,
            $info['flag'] => true,
        ]);
    }

    private function previewRequest(Request $request, string $type): View
    {
        $data = $request->validate($this->requestRules());
        $request->session()->put("{$type}_request", $data);

        return view('citizen.service-request-preview', [
            'requestData' => $data,
            'documentTitle' => self::TYPES[$type]['title'],
            'type' => $type,
        ]);
    }

    private function modifyRequest(Request $request, string $type): RedirectResponse
    {
        $data = $request->session()->get("{$type}_request");

        return is_array($data)
            ? redirect()->route(self::TYPES[$type]['route'])->withInput($data)
            : $this->previewExpired($type);
    }

    private function proceedRequest(Request $request, string $type): RedirectResponse
    {
        $data = $request->session()->get("{$type}_request");

        if (! is_array($data)) {
            return $this->previewExpired($type);
        }

        $validated = validator($data, $this->requestRules())->validate();

        $serviceRequest = DB::transaction(function () use ($request, $type, $validated) {
            if ($this->pendingQuery($request, $type)->lockForUpdate()->exists()) {
                return null;
            }

            $serviceRequest = ServiceRequest::create([
                'user_id' => $request->user()->id,
                'reference_number' => 'BRGY-'.now()->format('YmdHis').'-'.str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'service_type' => self::TYPES[$type]['service'],
                'applicant_name' => $validated['name'],
                'address' => $validated['address'],
                'age' => $validated['age'],
                'purpose' => $validated['purpose'],
                'status' => ServiceRequest::PENDING,
            ]);

            // Open a ticket with an automatic first message
            $serviceRequest->ticket()
                ->create(['user_id' => $request->user()->id])
                ->messages()
                ->create([
                    'body' => "Your request {$serviceRequest->reference_number} was submitted. The barangay will post updates here.",
                    'is_system' => true,
                ]);

            return $serviceRequest;
        });

        $request->session()->forget("{$type}_request");

        if (! $serviceRequest) {
            return $this->pendingBlocked();
        }

        $this->notifyAdmins($request->user(), $serviceRequest);

        return redirect()
            ->route('citizen.applications')
            ->with('success', "Your certificate request {$serviceRequest->reference_number} was submitted for barangay processing.");
    }

    // A failed notification must not break the submission.
    private function notifyAdmins(User $user, ServiceRequest $serviceRequest): void
    {
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CitizenSubmissionReceived(
                    'New permit request',
                    "{$user->name} submitted {$serviceRequest->reference_number} for review.",
                    route('admin.permits.show', $serviceRequest),
                ));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function ownedRequests(Request $request)
    {
        return ServiceRequest::whereBelongsTo($request->user())->latest();
    }

    private function pendingQuery(Request $request, string $type)
    {
        return ServiceRequest::whereBelongsTo($request->user())
            ->where('service_type', self::TYPES[$type]['service'])
            ->where('status', ServiceRequest::PENDING);
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
     * Locks the request, validates the printable document and counts the print.
     * The first pass skips counting when a QR upgrade is still needed;
     * the pass after the upgrade aborts if the QR code is still missing.
     *
     * @return array{document: IssuedDocument, fileContents: string, requiresQrUpgrade: bool}
     */
    private function recordPrint(ServiceRequest $serviceRequest, bool $afterUpgrade = false): array
    {
        return DB::transaction(function () use ($serviceRequest, $afterUpgrade) {
            $locked = $this->lock($serviceRequest);
            $printData = $this->printableDocument($locked);

            abort_if(
                $afterUpgrade && $printData['requiresQrUpgrade'],
                409,
                'The document QR code could not be generated. Please contact the barangay office.'
            );

            if (! $printData['requiresQrUpgrade']) {
                $locked->increment('print_count');
            }

            return $printData;
        });
    }

    /**
     * @return array{document: IssuedDocument, fileContents: string, requiresQrUpgrade: bool}
     */
    private function printableDocument(ServiceRequest $serviceRequest): array
    {
        abort_unless($serviceRequest->status === ServiceRequest::APPROVED, 404);
        abort_unless($serviceRequest->canPrint(), 403, 'This permit has reached its print limit.');

        $document = $serviceRequest->issuedDocuments()
            ->whereNull('revoked_at')
            ->latest('version')
            ->first();

        abort_unless($document, 404, 'No valid issued document was found.');

        $fileContents = Storage::disk($document->disk)->get($document->file_path);

        abort_unless(
            hash_equals($document->file_hash, hash('sha256', $fileContents)),
            409,
            'The document failed its integrity check. Please contact the barangay office.'
        );

        return [
            'document' => $document,
            'fileContents' => $fileContents,
            'requiresQrUpgrade' => $document->qr_code_generated_at === null,
        ];
    }

    private function documentTitle(ServiceRequest $serviceRequest): string
    {
        return collect(self::TYPES)->firstWhere('service', $serviceRequest->service_type)['title']
            ?? self::TYPES['indigency']['title'];
    }

    private function requestRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'purpose' => ['required', 'string', 'max:500'],
        ];
    }

    private function previewExpired(string $type): RedirectResponse
    {
        return redirect()
            ->route(self::TYPES[$type]['route'])
            ->withErrors(['purpose' => 'Your preview expired. Please fill out the form again.']);
    }

    private function pendingBlocked(): RedirectResponse
    {
        return redirect()
            ->route('citizen.applications')
            ->with('error', 'You already have a pending request for this service.');
    }
}