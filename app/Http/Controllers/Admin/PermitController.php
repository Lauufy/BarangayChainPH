<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Notifications\ServiceRequestApproved;
use App\Notifications\ServiceRequestRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PermitController extends Controller
{
    public function index(): View
    {
        $requests = ServiceRequest::with('user')
            ->latest()
            ->paginate(10);

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
        $serviceRequest->load('user');

        return view('admin.permit-review', [
            'application' => $serviceRequest,
        ]);
    }

    public function approve(ServiceRequest $serviceRequest): RedirectResponse
    {
        $approvedRequest = DB::transaction(function () use ($serviceRequest): ?ServiceRequest {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($serviceRequest->status !== ServiceRequest::PENDING) {
                return null;
            }

            $serviceRequest->update([
                'status' => ServiceRequest::APPROVED,
                'permit_number' => $serviceRequest->permit_number ?? $this->generatePermitNumber(),
                'rejection_reason' => null,
            ]);

            return $serviceRequest->fresh();
        });

        if (! $approvedRequest) {
            return redirect()
                ->route('admin.permits.show', $serviceRequest)
                ->with('error', 'Only pending applications can be approved.');
        }

        try {
            $approvedRequest->user->notify(new ServiceRequestApproved($approvedRequest));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('admin.permits.show', $serviceRequest)
            ->with('success', 'The application was approved.');
    }

    private function generatePermitNumber(): string
    {
        return 'PERMIT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }

    public function reject(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $rejectedRequest = DB::transaction(function () use ($serviceRequest, $validated): ?ServiceRequest {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($serviceRequest->status !== ServiceRequest::PENDING) {
                return null;
            }

            $serviceRequest->update([
                'status' => ServiceRequest::REJECTED,
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            return $serviceRequest->fresh();
        });

        if (! $rejectedRequest) {
            return redirect()
                ->route('admin.permits.show', $serviceRequest)
                ->with('error', 'Only pending applications can be rejected.');
        }

        try {
            $rejectedRequest->user->notify(new ServiceRequestRejected($rejectedRequest));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('admin.permits.show', $serviceRequest)
            ->with('success', 'The application was rejected.');
    }

    public function updatePrintLimit(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'print_limit' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        if ($serviceRequest->status !== ServiceRequest::APPROVED) {
            return redirect()
                ->route('admin.permits.show', $serviceRequest)
                ->with('error', 'Only approved applications can have their print limit changed.');
        }

        if ($validated['print_limit'] < $serviceRequest->print_count) {
            return redirect()
                ->route('admin.permits.show', $serviceRequest)
                ->withErrors([
                    'print_limit' => "The print limit cannot be lower than the {$serviceRequest->print_count} prints already used.",
                ]);
        }

        $serviceRequest->forceFill([
            'print_limit' => $validated['print_limit'],
        ])->save();

        return redirect()
            ->route('admin.permits.show', $serviceRequest)
            ->with('success', 'The permit print limit was updated.');
    }
}
