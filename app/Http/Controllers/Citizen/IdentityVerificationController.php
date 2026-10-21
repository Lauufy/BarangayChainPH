<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Notifications\CitizenSubmissionReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class IdentityVerificationController extends Controller
{
    private const MAX_REJECTED_TRIES = 3;

    private const DOCUMENT_TYPES = [
        'Philippine National ID',
        "Driver's License",
        'Passport',
        'UMID',
        'Postal ID',
        "Voter's ID",
        'PRC ID',
        'Barangay ID',
    ];

    // Show the citizen's details form, document form, or current status.
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->citizenProfile;

        $step = $request->integer('step') === 2 && $profile?->hasCompleteDetails()
            ? 2
            : 1;

        return view('citizen.identity-verification', [
            'profile' => $profile,
            'verification' => $user->latestIdentityVerification,
            'status' => $user->identityStatus(),
            'step' => $step,
            'documentTypes' => self::DOCUMENT_TYPES,
        ]);
    }

    // Save the citizen's personal details.
    public function storeDetails(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->isLocked($user)) {
            return $this->backToStatus();
        }

        $data = $request->validate([
            'phone' => ['required', 'max:15', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'date_of_birth' => 'required|date|before:today|after:1900-01-01',
            'gender' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'nationality' => 'required|string|max:100',
            'place_of_birth' => 'required|string|max:255',
            'religion' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'barangay' => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'zip_code' => 'required|string|max:10',
        ], [
            'phone.regex' => 'Enter a valid phone number.',
        ]);

        $data['address'] = implode(', ', array_filter([
            $data['house_number'] ?? null,
            $data['barangay'],
            $data['city_municipality'],
            $data['province'],
            $data['zip_code'],
        ]));

        $user->citizenProfile()->updateOrCreate([], $data);

        return redirect()->route('citizen.identity-verification', ['step' => 2]);
    }

    // Submit the government ID, selfie, and consent.
    public function storeDocument(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->isLocked($user)) {
            return $this->backToStatus();
        }

        if (! $user->citizenProfile?->hasCompleteDetails()) {
            return $this->backToStatus()->withErrors([
                'details' => 'Please complete your details first.',
            ]);
        }

        $data = $request->validate([
            ...$this->idRules(),
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'selfie' => 'required|mimes:jpg,jpeg,png|max:5120',
            'selfie_source' => 'required|in:camera,upload',
            'agree_truth' => 'accepted',
            'agree_privacy' => 'accepted',
        ], [
            ...$this->idMessages(),
            'document.mimes' => 'The ID must be a JPG, PNG, or PDF file.',
            'document.max' => 'The ID must not be larger than 5 MB.',
            'selfie.mimes' => 'The selfie must be a JPG or PNG photo.',
            'selfie.max' => 'The selfie must not be larger than 5 MB.',
            'agree_truth.accepted' => 'Please confirm that your information is true.',
            'agree_privacy.accepted' => 'Please agree to the data privacy notice.',
        ]);

        $rejected = $user->identityVerifications()
            ->where('status', IdentityVerification::REJECTED)
            ->count();

        if ($rejected >= self::MAX_REJECTED_TRIES) {
            return back()->withErrors([
                'limit' => 'Too many rejected attempts. Please visit the barangay office.',
            ]);
        }

        $hash = $this->idNumberHash($data['document_type'], $data['id_number']);

        if ($this->idAlreadyUsed($hash, $user->id)) {
            return back()->withErrors([
                'id_number' => 'This ID is already used by another account.',
            ]);
        }

        $document = $request->file('document');

        $user->identityVerifications()->create([
            'status' => IdentityVerification::PENDING,
            'document_type' => $data['document_type'],
            'document_path' => $document->store("identity-documents/{$user->id}", 'local'),
            'document_original_name' => $document->getClientOriginalName(),
            'id_number_hash' => $hash,
            'selfie_path' => $request->file('selfie')->store("identity-selfies/{$user->id}", 'local'),
            'selfie_source' => $data['selfie_source'],
            'consented_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->notifyAdmins($user);

        return $this->backToStatus()->with(
            'success',
            'Your application was submitted. Please wait for review.'
        );
    }

    // Check whether an ID number is already in use.
    public function checkIdNumber(Request $request): JsonResponse
    {
        $data = $request->validate($this->idRules(), $this->idMessages());

        $hash = $this->idNumberHash($data['document_type'], $data['id_number']);

        return response()->json([
            'used' => $this->idAlreadyUsed($hash, $request->user()->id),
        ]);
    }

    // Allow citizens to open only their own uploaded ID.
    public function document(Request $request, IdentityVerification $verification)
    {
        abort_unless(
            $verification->user_id === $request->user()->id
                && is_file($verification->filePath()),
            404
        );

        return response()->file($verification->filePath());
    }

    // Validation rules shared by submission and the live duplicate check.
    private function idRules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(self::DOCUMENT_TYPES)],
            'id_number' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-\s]+$/'],
        ];
    }

    private function idMessages(): array
    {
        return [
            'id_number.regex' => 'The ID number may only contain letters, numbers, and dashes.',
        ];
    }

    private function backToStatus(): RedirectResponse
    {
        return redirect()->route('citizen.identity-verification');
    }

    // Prevent changes while an application is pending or verified.
    private function isLocked(User $user): bool
    {
        return in_array($user->identityStatus(), ['pending', 'verified'], true);
    }

    // Hash the normalized ID number instead of storing the original.
    private function idNumberHash(string $type, string $number): string
    {
        $clean = preg_replace('/[^a-z0-9]/', '', strtolower($number));

        return hash_hmac('sha256', "{$type}|{$clean}", config('app.key'));
    }

    // Check other accounts with pending or verified applications.
    private function idAlreadyUsed(string $hash, int $userId): bool
    {
        return IdentityVerification::query()
            ->where('id_number_hash', $hash)
            ->where('user_id', '!=', $userId)
            ->whereIn('status', [
                IdentityVerification::PENDING,
                IdentityVerification::VERIFIED,
            ])
            ->exists();
    }

    // A failed notification must not break the submission.
    private function notifyAdmins(User $user): void
    {
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, new CitizenSubmissionReceived(
                'New identity verification',
                "{$user->name} submitted an identity verification application.",
                route('admin.identity-verification.index')
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}