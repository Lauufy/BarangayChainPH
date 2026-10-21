<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Notifications\ServiceRequestApproved;
use App\Notifications\ServiceRequestRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermitController extends Controller
{
    public function index(): View
    {
        $requests = ServiceRequest::with('user')->latest()->paginate(10);

        $requestCounts = [
            'total' => ServiceRequest::count(),
            'pending' => ServiceRequest::where('status', ServiceRequest::PENDING)->count(),
            'processing' => ServiceRequest::where('status', ServiceRequest::PROCESSING)->count(),
            'completed' => ServiceRequest::where('status', ServiceRequest::APPROVED)->count(),
        ];

        return view('admin.permits', compact('requests', 'requestCounts'));
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        return view('admin.modals-admin.permit-review', [
            'application' => $serviceRequest->load('user'),
        ]);
    }

    public function approve(ServiceRequest $serviceRequest): RedirectResponse
    {
        $approved = $this->decide($serviceRequest, fn($request) => [
            'status' => ServiceRequest::APPROVED,
            'permit_number' => $request->permit_number ?? ServiceRequest::generatePermitNumber(),
            'rejection_reason' => null,
        ]);

        if (! $approved) {
            return $this->back($serviceRequest, 'error', 'Only pending applications can be approved.');
        }

        $this->notify($approved, new ServiceRequestApproved($approved));

        $approved->ticket?->postSystemMessage('Your request was approved. You can now print your permit from the Documents page.', close: true);

        return $this->back($serviceRequest, 'success', 'The application was approved.');
    }

    public function reject(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $rejected = $this->decide($serviceRequest, fn() => [
            'status' => ServiceRequest::REJECTED,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        if (! $rejected) {
            return $this->back($serviceRequest, 'error', 'Only pending applications can be rejected.');
        }

        $this->notify($rejected, new ServiceRequestRejected($rejected));

        $rejected->ticket?->postSystemMessage('Your request was rejected. Reason: ' . $data['rejection_reason'], close: true);

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

    /** Locks the row and applies the update only if the request is still pending. */
    private function decide(ServiceRequest $serviceRequest, callable $attributes): ?ServiceRequest
    {
        return DB::transaction(function () use ($serviceRequest, $attributes) {
            $locked = ServiceRequest::whereKey($serviceRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ServiceRequest::PENDING) {
                return null;
            }

            $locked->update($attributes($locked));

            return $locked->fresh();
        });
    }

    private function notify(ServiceRequest $serviceRequest, $notification): void
    {
        try {
            $serviceRequest->user->notify($notification);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function back(ServiceRequest $serviceRequest, string $type, string $message): RedirectResponse
    {
        return redirect()->route('admin.permits.show', $serviceRequest)->with($type, $message);
    }
}
