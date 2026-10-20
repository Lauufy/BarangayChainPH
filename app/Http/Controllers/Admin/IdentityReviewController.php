<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Notifications\IdentityVerificationReviewed;
use Illuminate\Http\Request;

class IdentityReviewController extends Controller
{
    // List all applications with counts
    public function index(Request $request)
    {
        return view('admin.identity-verification', [
            'prefix' => $request->user()->role, // "admin" or "staff"
            'counts' => IdentityVerification::selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status'),
            'verifications' => IdentityVerification::with('user')->latest('submitted_at')->paginate(10),
        ]);
    }

    // Show one application
    public function show(Request $request, IdentityVerification $verification)
    {
        $verification->load('user.citizenProfile', 'reviewer');

        return view('admin.modals-admin.identity-verification-show', [
            'prefix' => $request->user()->role,
            'verification' => $verification,
            'profile' => $verification->user->citizenProfile,
            'isImage' => !str_ends_with($verification->document_path, '.pdf'),
            'rejectedBefore' => IdentityVerification::where('user_id', $verification->user_id)
                ->where('status', IdentityVerification::REJECTED)->count(),
        ]);
    }

    // Open the uploaded ID
    public function document(IdentityVerification $verification)
    {
        abort_unless(is_file($verification->filePath()), 404);

        return response()->file($verification->filePath());
    }

    // Open the selfie
    public function selfie(IdentityVerification $verification)
    {
        abort_unless($verification->selfie_path && is_file($verification->selfiePath()), 404);

        return response()->file($verification->selfiePath());
    }

    public function approve(Request $request, IdentityVerification $verification)
    {
        return $this->review($request, $verification, IdentityVerification::VERIFIED);
    }

    public function reject(Request $request, IdentityVerification $verification)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:500',
        ]);

        return $this->review($request, $verification, IdentityVerification::REJECTED, $request->rejection_reason);
    }

    // Save the decision (only pending applications can be reviewed)
    private function review(Request $request, IdentityVerification $verification, string $status, ?string $reason = null)
    {
        $route = $request->user()->role . '.identity-verification.index';

        if (!$verification->isPending()) {
            return redirect()->route($route)->with('error', 'This application was already reviewed.');
        }

        $verification->update([
            'status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // A mail problem must never undo the decision
        try {
            $verification->user->notify(new IdentityVerificationReviewed($verification));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route($route)->with('success', 'The application was ' . $status . '.');
    }
}
