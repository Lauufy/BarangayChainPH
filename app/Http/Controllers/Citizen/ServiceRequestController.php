<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function indigency(Request $request): View
    {
        return view('citizen.service-request', [
            'title' => 'Certificate of indigency',
            'description' => 'Ask the barangay about eligibility and requirements for this certificate.',
            'profile' => $request->user()->citizenProfile,
            'isIndigency' => true,
        ]);
    }

    public function clearance(Request $request): View
    {
        return view('citizen.service-request', [
            'title' => 'Barangay clearance',
            'description' => 'Request a clearance for employment, business, or other official purposes.',
            'profile' => $request->user()->citizenProfile,
            'isClearance' => true,
        ]);
    }

    public function residency(Request $request): View
    {
        return view('citizen.service-request', [
            'title' => 'Certificate of residency',
            'description' => 'Request proof of residency within the barangay.',
            'profile' => $request->user()->citizenProfile,
            'isResidency' => true,
        ]);
    }

    public function applications(Request $request): View
    {
        $applications = ServiceRequest::query()
            ->whereBelongsTo($request->user())
            ->where('status', '!=', ServiceRequest::APPROVED)
            ->latest()
            ->paginate(10);

        return view('citizen.applications', compact('applications'));
    }

    public function documents(Request $request): View
    {
        $documents = ServiceRequest::query()
            ->whereBelongsTo($request->user())
            ->where('status', ServiceRequest::APPROVED)
            ->latest()
            ->paginate(10);

        return view('citizen.documents', compact('documents'));
    }

    public function showApplication(Request $request, ServiceRequest $serviceRequest): View
    {
        abort_unless($serviceRequest->user_id === $request->user()->id, 404);

        return view('citizen.application-review', [
            'application' => $serviceRequest,
            'documentTitle' => $this->documentTitle($serviceRequest),
        ]);
    }

    public function printApplication(Request $request, ServiceRequest $serviceRequest): View
    {
        abort_unless($serviceRequest->user_id === $request->user()->id, 404);

        $serviceRequest = DB::transaction(function () use ($serviceRequest): ServiceRequest {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($serviceRequest->status === ServiceRequest::APPROVED, 404);
            abort_unless($serviceRequest->canPrint(), 403, 'This permit has reached its print limit.');

            $serviceRequest->update([
                'permit_number' => $serviceRequest->permit_number ?? 'PERMIT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'print_count' => $serviceRequest->print_count + 1,
            ]);

            return $serviceRequest->fresh();
        });

        $verificationUrl = URL::signedRoute('permits.verify', [
            'serviceRequest' => $serviceRequest,
        ]);
        $qrCode = (new Builder(
            writer: new SvgWriter,
            data: $verificationUrl,
            size: 120,
            margin: 4,
        ))->build()
            ->getDataUri();

        return view('citizen.application-print', [
            'application' => $serviceRequest,
            'documentTitle' => $this->documentTitle($serviceRequest),
            'qrCode' => $qrCode,
            'verificationUrl' => $verificationUrl,
        ]);
    }

    public function cancelApplication(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless($serviceRequest->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $wasCancelled = DB::transaction(function () use ($serviceRequest, $validated): bool {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $serviceRequest->isCancellable()) {
                return false;
            }

            $serviceRequest->update([
                'status' => ServiceRequest::CANCELLED,
                'cancellation_reason' => $validated['cancellation_reason'],
                'cancelled_at' => now(),
                'permit_number' => null,
                'rejection_reason' => null,
            ]);

            return true;
        });

        if (! $wasCancelled) {
            return redirect()
                ->route('citizen.applications.show', $serviceRequest)
                ->withErrors(['cancellation_reason' => 'This application can only be canceled while pending and within two hours of submission.']);
        }

        return redirect()
            ->route('citizen.applications.show', $serviceRequest)
            ->with('success', 'Your application was canceled successfully.');
    }

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

    public function proceedClearance(Request $request): RedirectResponse
    {
        return $this->proceedRequest($request, 'clearance');
    }

    public function proceedResidency(Request $request): RedirectResponse
    {
        return $this->proceedRequest($request, 'residency');
    }

    private function previewRequest(Request $request, string $type): View
    {
        $data = $request->validate($this->requestRules());
        $request->session()->put("{$type}_request", $data);

        return view('citizen.service-request-preview', [
            'requestData' => $data,
            'documentTitle' => $this->documentTitleForType($type),
            'type' => $type,
        ]);
    }

    private function modifyRequest(Request $request, string $type): RedirectResponse
    {
        $data = $request->session()->get("{$type}_request");

        if (! is_array($data)) {
            return redirect()
                ->route($this->formRouteForType($type))
                ->withErrors(['purpose' => 'Your preview expired. Please fill out the form again.']);
        }

        return redirect()
            ->route($this->formRouteForType($type))
            ->withInput($data);
    }

    public function proceedIndigency(Request $request): RedirectResponse
    {
        return $this->proceedRequest($request, 'indigency');
    }

    private function proceedRequest(Request $request, string $type): RedirectResponse
    {
        $data = $request->session()->get("{$type}_request");

        if (! is_array($data)) {
            return redirect()
                ->route($this->formRouteForType($type))
                ->withErrors(['purpose' => 'Your preview expired. Please fill out the form again.']);
        }

        $validated = validator($data, $this->requestRules())->validate();

        $serviceRequest = DB::transaction(function () use ($request, $validated, $type): ServiceRequest {
            $serviceRequest = ServiceRequest::create([
                'user_id' => $request->user()->id,
                'reference_number' => 'BRGY-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'service_type' => $this->serviceTypeFor($type),
                'applicant_name' => $validated['name'],
                'address' => $validated['address'],
                'age' => $validated['age'],
                'purpose' => $validated['purpose'],
                'status' => ServiceRequest::PENDING,
            ]);

            $request->session()->forget("{$type}_request");

            return $serviceRequest;
        });

        return redirect()
            ->route('citizen.applications')
            ->with('success', "Your certificate request {$serviceRequest->reference_number} was submitted for barangay processing.");
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

    private function documentTitle(ServiceRequest $serviceRequest): string
    {
        return match ($serviceRequest->service_type) {
            ServiceRequest::BARANGAY_CLEARANCE => 'Barangay Clearance',
            ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of Residency',
            default => 'Certificate of Indigency',
        };
    }

    private function documentTitleForType(string $type): string
    {
        return $this->documentTitle((new ServiceRequest([
            'service_type' => $this->serviceTypeFor($type),
        ])));
    }

    private function serviceTypeFor(string $type): string
    {
        return match ($type) {
            'clearance' => ServiceRequest::BARANGAY_CLEARANCE,
            'residency' => ServiceRequest::CERTIFICATE_OF_RESIDENCY,
            default => ServiceRequest::CERTIFICATE_OF_INDIGENCY,
        };
    }

    private function formRouteForType(string $type): string
    {
        return match ($type) {
            'clearance' => 'citizen.barangay-clearance',
            'residency' => 'citizen.certificate-of-residency',
            default => 'citizen.certificate-of-indigency',
        };
    }
}
