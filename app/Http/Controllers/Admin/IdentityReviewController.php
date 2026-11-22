<?php

namespace App\Http\Controllers\Admin;

use App\Exports\IdentityVerificationsExport;
use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Notifications\IdentityVerificationReviewed;
use App\Services\SupabaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Reviews citizen identity verification submissions. */
class IdentityReviewController extends Controller
{
    public function __construct(private SupabaseStorage $supabaseStorage) {}

    // List all applications with counts and latest dates
    public function index(Request $request, IdentityVerificationsExport $export)
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 10;
        $stats = IdentityVerification::query()
            ->selectRaw('status, count(*) as total, max(submitted_at) as last_submitted, max(reviewed_at) as last_reviewed')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('admin.identity-verifications.index', [
            'counts' => $stats->pluck('total', 'status'),

            // Date each count last changed: pending = newest submission, others = newest review
            'latest' => [
                'pending' => $stats[IdentityVerification::PENDING]->last_submitted ?? null,
                'verified' => $stats[IdentityVerification::VERIFIED]->last_reviewed ?? null,
                'rejected' => $stats[IdentityVerification::REJECTED]->last_reviewed ?? null,
            ],

            'verifications' => $export->query($request)->paginate($perPage)->appends($request->except('search')),
        ]);
    }

    // Show one application
    public function show(Request $request, IdentityVerification $verification)
    {
        $verification->load('user.citizenProfile', 'reviewer');
        $user = $verification->user;
        $documentUrl = route('admin.identity-verification.document', $verification);
        $selfieUrl = route('admin.identity-verification.selfie', $verification);
        $fields = [
            'Name' => $user->name,
            'Date of birth' => $user->citizenProfile?->date_of_birth?->format('M j, Y'),
            'Gender' => $user->citizenProfile?->gender,
            'Civil status' => $user->citizenProfile?->civil_status,
            'Nationality' => $user->citizenProfile?->nationality,
            'Place of birth' => $user->citizenProfile?->place_of_birth,
            'Religion' => $user->citizenProfile?->religion,
            'Occupation' => $user->citizenProfile?->occupation,
            'Phone' => $user->phone,
        ];
        $wideFields = [
            'Email' => $user->email,
            'Address' => $user->citizenProfile?->address,
        ];
        $selfieCaption = ! $verification->selfie_path
            ? 'Not provided'
            : ($verification->selfie_source === 'camera' ? 'Taken with the camera' : 'Uploaded photo');

        return view('admin.identity-verifications.show', [
            'verification' => $verification,
            'profile' => $user->citizenProfile,
            'user' => $user,
            'documentUrl' => $documentUrl,
            'selfieUrl' => $selfieUrl,
            'fields' => $fields,
            'wideFields' => $wideFields,
            'selfieCaption' => $selfieCaption,
            'isImage' => ! str_ends_with($verification->document_path, '.pdf'),
            'rejectedBefore' => IdentityVerification::where('user_id', $verification->user_id)
                ->where('status', IdentityVerification::REJECTED)->count(),
        ]);
    }

    // Open the uploaded ID
    public function document(IdentityVerification $verification): RedirectResponse
    {
        return redirect()->away(
            $this->supabaseStorage->temporaryUrl($verification->document_path, now()->addMinutes(10))
        );
    }

    // Open the selfie
    public function selfie(IdentityVerification $verification): RedirectResponse
    {
        abort_unless($verification->selfie_path, 404);

        return redirect()->away(
            $this->supabaseStorage->temporaryUrl($verification->selfie_path, now()->addMinutes(10))
        );
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
        $route = 'admin.identity-verification.index';

        if (! $verification->isPending()) {
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

        return redirect()->route($route)->with('success', 'The application was '.$status.'.');
    }
}
