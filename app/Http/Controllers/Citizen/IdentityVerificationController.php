<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\FinalizeIdentityVerificationRequest;
use App\Http\Requests\Citizen\StoreIdentityVerificationIdRequest;
use App\Http\Requests\Citizen\StoreIdentityVerificationSelfieRequest;
use App\Jobs\ProcessIdentityOcr;
use App\Models\CitizenProfile;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Notifications\CitizenSubmissionReceived;
use App\Services\AuditLogger;
use App\Services\SupabaseStorage;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Manages citizen identity verification submissions and private uploads.
class IdentityVerificationController extends Controller
{
    public function __construct(private SupabaseStorage $supabaseStorage) {}

    private const DRAFT_SESSION_KEY = 'identity_verification.draft';

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
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->citizenProfile;
        $draft = $request->session()->get(self::DRAFT_SESSION_KEY, []);
        $legacyDraft = $this->draftFor($user);

        if ($legacyDraft && empty($draft['legacy_verification_id'])) {
            $draft = array_merge([
                'legacy_verification_id' => $legacyDraft->id,
                'document_type' => $legacyDraft->document_type,
                'document_path' => $legacyDraft->document_path,
                'document_back_key' => $legacyDraft->document_back_key,
                'selfie_path' => $legacyDraft->selfie_path,
                'selfie_source' => $legacyDraft->selfie_source,
            ], $draft);
            $request->session()->put(self::DRAFT_SESSION_KEY, $draft);
        }

        $status = $user->identityStatus();
        $formDetails = $draft['details'] ?? $this->detailsFromProfile($user, $profile);
        $hasCompleteDetails = $this->hasCompleteDetails($formDetails)
            || ($profile?->hasCompleteDetails() ?? false);

        $step = 1;

        if ($hasCompleteDetails) {
            $step = $request->integer('step') === 1
                ? 1
                : ($this->hasSavedVerificationStep($draft)
                    ? $this->firstIncompleteStep($draft)
                    : ($request->integer('step') === 2 ? 2 : 1));
        }

        $verification = $this->draftFor($user);

        if (! $verification && in_array($status, [IdentityVerification::PENDING, IdentityVerification::VERIFIED], true)) {
            $verification = $user->latestIdentityVerification;
        }

        return view('citizen.identity-verifications.index', [
            'user' => $user,
            'verification' => $verification,
            'draft' => $draft,
            'formDetails' => $formDetails,
            'draftPreviewUrls' => [
                'document' => $this->draftPreviewUrl($draft, 'document_path', 'document', $user),
                'document_back' => $this->draftPreviewUrl($draft, 'document_back_key', 'document_back', $user),
                'selfie' => $this->draftPreviewUrl($draft, 'selfie_path', 'selfie', $user),
            ],
            'status' => $status,
            'step' => $step,
            'documentTypes' => self::DOCUMENT_TYPES,
        ]);
    }

    // Keep the citizen's personal details in the session until final submission.
    public function storeDetails(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->isLocked($user)) {
            return $this->backToStatus();
        }

        $phoneInput = $request->input('phone');
        $request->merge([
            'phone' => is_string($phoneInput) ? Phone::normalize($phoneInput) : null,
        ]);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            // Email is only asked when the account has none (SMS sign-up)
            'email' => [
                Rule::excludeIf($user->email !== null),
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
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
            'phone.required' => 'Enter a valid phone number.',
        ]);

        $draft = $request->session()->get(self::DRAFT_SESSION_KEY, []);
        $draft['details'] = $data;
        $request->session()->put(self::DRAFT_SESSION_KEY, $draft);

        return redirect()->route('citizen.identity-verification', ['step' => 2]);
    }

    // Stage both ID images privately and keep only their keys in the session.
    public function storeId(StoreIdentityVerificationIdRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($failure = $this->editingFailure($request)) {
            return $failure;
        }

        $data = $request->validated();
        $draft = $request->session()->get(self::DRAFT_SESSION_KEY, []);
        if (empty($draft['legacy_verification_id'])
            && $this->rejectedAttempts($user) >= self::MAX_REJECTED_TRIES) {
            return $this->endpointError(
                $request,
                'limit',
                'Too many rejected attempts. Please visit the barangay office.',
                429
            );
        }

        $document = $request->file('document');
        $documentBack = $request->file('document_back');
        $documentPath = null;
        $documentBackKey = null;
        $oldPath = $draft['document_path'] ?? null;
        $oldBackKey = $draft['document_back_key'] ?? null;

        try {
            if ($document) {
                $documentPath = $this->stageIdentityFile($document, $user);
            }

            if ($documentBack) {
                $documentBackKey = $this->stageIdentityFile($documentBack, $user);
            }

            if (($document && ! $documentPath) || ($documentBack && ! $documentBackKey)) {
                throw new \RuntimeException('The identity document could not be stored.');
            }

            if ($this->hasActiveVerification($user)) {
                $this->discardFiles([$documentPath, $documentBackKey]);

                return $this->endpointError($request, 'verification', 'An active verification already exists.', 409);
            }

            if ($documentPath) {
                $draft['document_path'] = $documentPath;
            }

            if ($documentBackKey) {
                $draft['document_back_key'] = $documentBackKey;
            }

            $draft['document_type'] = $data['document_type'];
            $request->session()->put(self::DRAFT_SESSION_KEY, $draft);

            if ($documentPath && $documentPath !== $oldPath) {
                $this->deleteStagedFile($oldPath, $user);
            }

            if ($documentBackKey && $documentBackKey !== $oldBackKey) {
                $this->deleteStagedFile($oldBackKey, $user);
            }
        } catch (\Throwable $exception) {
            $this->discardFiles([$documentPath, $documentBackKey]);
            report($exception);

            return $this->endpointError($request, 'document', 'We could not save your ID photos. Please try again.', 500);
        }

        $response = [
            'document_preview_url' => route('citizen.identity-verification.staged-file', ['type' => 'document']),
            'document_is_pdf' => str_ends_with(strtolower($draft['document_path']), '.pdf'),
            'document_back_preview_url' => route('citizen.identity-verification.staged-file', ['type' => 'document_back']),
            'document_back_is_pdf' => str_ends_with(strtolower($draft['document_back_key']), '.pdf'),
        ];

        if ($request->expectsJson()) {
            return response()->json(['ok' => true] + $response);
        }

        return redirect()->route('citizen.identity-verification');
    }

    // Stage the selfie privately and keep its key in the session.
    public function storeSelfie(StoreIdentityVerificationSelfieRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($failure = $this->editingFailure($request)) {
            return $failure;
        }

        $data = $request->validated();
        $selfie = $request->file('selfie');
        $selfiePath = null;
        $draft = $request->session()->get(self::DRAFT_SESSION_KEY, []);
        $oldPath = $draft['selfie_path'] ?? null;

        try {
            if ($selfie) {
                $selfiePath = $this->stageIdentityFile($selfie, $user);
            }

            if ($selfie && ! $selfiePath) {
                throw new \RuntimeException('The selfie could not be stored.');
            }

            if ($this->hasActiveVerification($user)) {
                $this->discardFiles([$selfiePath]);

                return $this->endpointError($request, 'verification', 'An active verification already exists.', 409);
            }

            if (blank($draft['document_path'] ?? null) || blank($draft['document_back_key'] ?? null)) {
                $this->discardFiles([$selfiePath]);

                return $this->endpointError($request, 'document', 'Save both sides of your ID before uploading a selfie.');
            }

            if ($selfiePath) {
                $draft['selfie_path'] = $selfiePath;
            }

            $draft['selfie_source'] = $data['selfie_source'];
            $request->session()->put(self::DRAFT_SESSION_KEY, $draft);

            if ($selfiePath && $selfiePath !== $oldPath) {
                $this->deleteStagedFile($oldPath, $user);
            }
        } catch (\Throwable $exception) {
            $this->discardFiles([$selfiePath]);
            report($exception);

            return $this->endpointError($request, 'selfie', 'We could not save your file. Please try again.', 500);
        }

        $response = [
            'selfie_preview_url' => route('citizen.identity-verification.staged-file', ['type' => 'selfie']),
        ];

        if ($request->expectsJson()) {
            return response()->json(['ok' => true] + $response);
        }

        return redirect()->route('citizen.identity-verification');
    }

    // Persist the session draft and its staged files only after final consent.
    public function storeDocument(FinalizeIdentityVerificationRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($failure = $this->editingFailure($request)) {
            return $failure;
        }

        $draft = $request->session()->get(self::DRAFT_SESSION_KEY, []);
        $details = $draft['details'] ?? $this->detailsFromProfile($user, $user->citizenProfile);
        $requiredFiles = [
            'document_path' => 'document',
            'document_back_key' => 'document_back',
            'selfie_path' => 'selfie',
        ];

        foreach ($requiredFiles as $key => $field) {
            if (blank($draft[$key] ?? null)) {
                $message = match ($field) {
                    'document_back' => 'Please save the back of your ID before submitting.',
                    'selfie' => 'Please save your selfie before submitting.',
                    default => 'Please save the front of your ID before submitting.',
                };

                return $this->endpointError($request, $field, $message);
            }
        }

        if (! $this->hasCompleteDetails($details) || blank($draft['document_type'] ?? null)) {
            return $this->endpointError($request, 'details', 'Please complete your details and ID type before submitting.');
        }

        $legacyDraft = isset($draft['legacy_verification_id'])
            ? $user->identityVerifications()
                ->whereKey($draft['legacy_verification_id'])
                ->where('status', IdentityVerification::DRAFT)
                ->first()
            : null;
        $legacyPathByKey = $legacyDraft ? [
            'document_path' => $legacyDraft->document_path,
            'document_back_key' => $legacyDraft->document_back_key,
            'selfie_path' => $legacyDraft->selfie_path,
        ] : [];

        $finalPaths = [];
        $createdPaths = [];

        foreach (['document_path', 'document_back_key', 'selfie_path'] as $key) {
            $path = $draft[$key];

            if (! $this->isStagedPath($path, $user)
                && (! $legacyDraft || ($legacyPathByKey[$key] ?? null) !== $path)) {
                return $this->endpointError($request, 'document', 'Your saved files have expired. Please upload them again.');
            }
        }

        try {
            foreach ([
                'document_path' => 'identity-documents',
                'document_back_key' => 'identity-documents',
                'selfie_path' => 'identity-selfies',
            ] as $key => $folder) {
                $path = $draft[$key];

                if ($this->isStagedPath($path, $user)) {
                    $finalPaths[$key] = $this->promoteStagedFile($path, $folder, $user);
                    $createdPaths[] = $finalPaths[$key];

                    continue;
                }

                $finalPaths[$key] = $path;
            }

            $result = DB::transaction(function () use ($user, $draft, $details, $finalPaths): array {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

                if ($this->hasActiveVerification($lockedUser)) {
                    return ['error' => 'active'];
                }

                $existingDraft = isset($draft['legacy_verification_id'])
                    ? $lockedUser->identityVerifications()
                        ->whereKey($draft['legacy_verification_id'])
                        ->where('status', IdentityVerification::DRAFT)
                        ->lockForUpdate()
                        ->first()
                    : null;

                if (isset($draft['legacy_verification_id']) && ! $existingDraft) {
                    return ['error' => 'draft'];
                }

                if (! $existingDraft && $this->rejectedAttempts($lockedUser) >= self::MAX_REJECTED_TRIES) {
                    return ['error' => 'limit'];
                }

                $attributes = [
                    'status' => IdentityVerification::PENDING,
                    'document_type' => $draft['document_type'],
                    'document_path' => $finalPaths['document_path'],
                    'document_back_key' => $finalPaths['document_back_key'],
                    'selfie_path' => $finalPaths['selfie_path'],
                    'selfie_source' => $draft['selfie_source'] ?? 'upload',
                    'consented_at' => now(),
                    'submitted_at' => now(),
                ];

                $oldPaths = $existingDraft ? [
                    $existingDraft->document_path,
                    $existingDraft->document_back_key,
                    $existingDraft->selfie_path,
                ] : [];
                $this->persistDetails($lockedUser, $details);
                if ($existingDraft) {
                    $existingDraft->update($attributes);
                    $verification = $existingDraft;
                } else {
                    $verification = $lockedUser->identityVerifications()->create($attributes);
                }

                return [
                    'verification' => $verification,
                    'old_paths' => $oldPaths,
                ];
            });

            if (isset($result['error'])) {
                $this->discardFiles($createdPaths);

                return match ($result['error']) {
                    'active' => $this->endpointError($request, 'verification', 'An active verification already exists.', 409),
                    'limit' => $this->endpointError(
                        $request,
                        'limit',
                        'Too many rejected attempts. Please visit the barangay office.',
                        429
                    ),
                    default => $this->endpointError($request, 'document', 'Your saved draft has expired. Please start again.'),
                };
            }
        } catch (\Throwable $exception) {
            $this->discardFiles($createdPaths);
            report($exception);

            return $this->endpointError($request, 'document', 'We could not submit your verification. Please try again.', 500);
        }

        $verification = $result['verification'];
        $request->session()->forget(self::DRAFT_SESSION_KEY);
        $stagedPaths = array_intersect_key($draft, array_flip([
            'document_path',
            'document_back_key',
            'selfie_path',
        ]));
        $this->discardFiles(array_filter($stagedPaths, fn ($path): bool => $this->isStagedPath($path, $user)));
        $this->discardFiles(array_diff($result['old_paths'] ?? [], array_values($finalPaths)));

        $this->audit('identity.submitted', $verification, [
            'document_type' => $verification->document_type,
            'selfie_source' => $verification->selfie_source,
        ]);

        $this->queueOcrCheck($verification);
        $this->notifyAdmins($user);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => IdentityVerification::PENDING]);
        }

        return $this->backToStatus()->with(
            'success',
            'Your application was submitted. Please wait for review.'
        );
    }

    // Redirect a staged file through a short-lived URL for the current user's session.
    public function stagedFile(Request $request, string $type): RedirectResponse
    {
        $key = match ($type) {
            'document' => 'document_path',
            'document_back' => 'document_back_key',
            'selfie' => 'selfie_path',
            default => abort(404),
        };
        $path = data_get($request->session()->get(self::DRAFT_SESSION_KEY, []), $key);
        abort_unless($this->isStagedPath($path, $request->user()), 404);
        abort_unless(Storage::disk('supabase')->exists($path), 404);

        return redirect()->away(
            $this->supabaseStorage->temporaryUrl($path, now()->addMinutes(5)),
            302,
            ['Cache-Control' => 'private, no-store']
        );
    }

    // Allow citizens to open only their own uploaded ID.
    public function document(Request $request, IdentityVerification $verification): RedirectResponse
    {
        abort_unless($verification->user_id === $request->user()->id, 404);
        $type = $request->query('type', 'document');
        $path = match ($type) {
            'document' => $verification->document_path,
            'document_back' => $verification->document_back_key,
            'selfie' => $verification->selfie_path,
            default => null,
        };
        abort_if(blank($path), 404);

        $action = match ($type) {
            'selfie' => 'identity.selfie_viewed',
            'document_back' => 'identity.document_back_viewed',
            default => 'identity.document_viewed',
        };
        $this->audit($action, $verification);

        return redirect()->away(
            $this->supabaseStorage->temporaryUrl($path, now()->addMinutes(5)),
            302,
            ['Cache-Control' => 'private, no-store']
        );
    }

    private function backToStatus(): RedirectResponse
    {
        return redirect()->route('citizen.identity-verification');
    }

    // Prevent changes while an application is pending or verified.
    private function isLocked(User $user): bool
    {
        return $this->hasActiveVerification($user);
    }

    private function hasActiveVerification(User $user): bool
    {
        return $user->identityVerifications()
            ->whereIn('status', [IdentityVerification::PENDING, IdentityVerification::VERIFIED])
            ->exists();
    }

    private function draftFor(User $user): ?IdentityVerification
    {
        return $user->identityVerifications()
            ->where('status', IdentityVerification::DRAFT)
            ->latest('id')
            ->first();
    }

    private function hasSavedVerificationStep(array $draft): bool
    {
        return filled($draft['document_path'] ?? null)
            || filled($draft['document_back_key'] ?? null)
            || filled($draft['selfie_path'] ?? null);
    }

    private function firstIncompleteStep(array $draft): int
    {
        if (blank($draft['document_path'] ?? null) || blank($draft['document_back_key'] ?? null)) {
            return 2;
        }

        return blank($draft['selfie_path'] ?? null) ? 3 : 4;
    }

    private function rejectedAttempts(User $user): int
    {
        return $user->identityVerifications()
            ->where('status', IdentityVerification::REJECTED)
            ->count();
    }

    private function editingFailure(Request $request): JsonResponse|RedirectResponse|null
    {
        if ($this->isLocked($request->user())) {
            return $this->endpointError($request, 'verification', 'An active verification already exists.', 409);
        }

        $draftDetails = $request->session()->get(self::DRAFT_SESSION_KEY.'.details');

        if (! $this->hasCompleteDetails($draftDetails)
            && ! $request->user()->citizenProfile?->hasCompleteDetails()) {
            return $this->endpointError($request, 'details', 'Please complete your details first.');
        }

        return null;
    }

    private function endpointError(
        Request $request,
        string $field,
        string $message,
        int $status = 422
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [$field => [$message]],
            ], $status);
        }

        return back()->withErrors([$field => $message]);
    }

    private function draftPreviewUrl(array $draft, string $key, string $type, User $user): ?string
    {
        if (blank($draft[$key] ?? null)) {
            return null;
        }

        if (isset($draft['legacy_verification_id']) && ! $this->isStagedPath($draft[$key], $user)) {
            return route('citizen.identity-verification.file', [
                'verification' => $draft['legacy_verification_id'],
                'type' => $type,
            ]);
        }

        return route('citizen.identity-verification.staged-file', ['type' => $type]);
    }

    private function stageIdentityFile(UploadedFile $file, User $user): string
    {
        $extension = $file->guessExtension();
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
            throw new \RuntimeException('The uploaded identity file type could not be determined.');
        }

        $folder = 'identity-staging/'.now()->toDateString().'/'.$user->id;
        $path = $file->storeAs($folder, Str::uuid().'.'.$extension, 'supabase');
        if (! $path) {
            throw new \RuntimeException('The uploaded identity file could not be staged.');
        }

        return $path;
    }

    private function promoteStagedFile(string $path, string $folder, User $user): string
    {
        if (! $this->isStagedPath($path, $user) || ! Storage::disk('supabase')->exists($path)) {
            throw new \RuntimeException('A staged identity file is missing or invalid.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $destination = $folder.'/'.$user->id.'/'.Str::uuid().'.'.$extension;

        if (! Storage::disk('supabase')->copy($path, $destination)) {
            throw new \RuntimeException('A staged identity file could not be finalized.');
        }

        return $destination;
    }

    private function isStagedPath(?string $path, User $user): bool
    {
        if (! $path) {
            return false;
        }

        $segments = explode('/', $path);

        return count($segments) === 4
            && $segments[0] === 'identity-staging'
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $segments[1]) === 1
            && $segments[2] === (string) $user->id
            && preg_match('/^[0-9a-f-]{36}\.(jpg|jpeg|png|pdf)$/i', $segments[3]) === 1;
    }

    private function deleteStagedFile(?string $path, User $user): void
    {
        if ($this->isStagedPath($path, $user)) {
            $this->discardFiles([$path]);
        }
    }

    private function detailsFromProfile(User $user, ?CitizenProfile $profile): array
    {
        return [
            'phone' => $user->phone,
            'email' => $user->email,
            'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d'),
            'gender' => $profile?->gender,
            'civil_status' => $profile?->civil_status,
            'nationality' => $profile?->nationality,
            'place_of_birth' => $profile?->place_of_birth,
            'religion' => $profile?->religion,
            'occupation' => $profile?->occupation,
            'house_number' => $profile?->house_number,
            'barangay' => $profile?->barangay,
            'city_municipality' => $profile?->city_municipality,
            'province' => $profile?->province,
            'zip_code' => $profile?->zip_code,
        ];
    }

    private function hasCompleteDetails(?array $details): bool
    {
        if (! $details) {
            return false;
        }

        foreach ([
            'phone',
            'date_of_birth',
            'gender',
            'civil_status',
            'nationality',
            'place_of_birth',
            'barangay',
            'city_municipality',
            'province',
            'zip_code',
        ] as $field) {
            if (blank($details[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function persistDetails(User $user, array $details): void
    {
        $phone = $details['phone'];

        if ($user->phone !== $phone) {
            $user->update([
                'phone' => $phone,
                'phone_verified_at' => null,
            ]);
        }

        if (filled($details['email'] ?? null) && $user->email !== $details['email']) {
            $user->update(['email' => $details['email']]);
        }

        $profileData = [
            'date_of_birth' => $details['date_of_birth'],
            'gender' => $details['gender'],
            'civil_status' => $details['civil_status'],
            'nationality' => $details['nationality'],
            'place_of_birth' => $details['place_of_birth'],
            'religion' => $details['religion'] ?? null,
            'occupation' => $details['occupation'] ?? null,
            'house_number' => $details['house_number'] ?? null,
            'barangay' => $details['barangay'],
            'city_municipality' => $details['city_municipality'],
            'province' => $details['province'],
            'zip_code' => $details['zip_code'],
            'address' => implode(', ', array_filter([
                $details['house_number'] ?? null,
                $details['barangay'],
                $details['city_municipality'],
                $details['province'],
                $details['zip_code'],
            ])),
        ];

        $user->citizenProfile()->updateOrCreate([], $profileData);
    }

    // Delete uploaded files after a failed submission.
    private function discardFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (! $path) {
                continue;
            }

            try {
                if (! Storage::disk('supabase')->delete($path)) {
                    report(new \RuntimeException('An identity file could not be deleted.'));
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    // A logging problem must never break the citizen's request.
    private function audit(string $action, Model $subject, array $values = []): void
    {
        try {
            app(AuditLogger::class)->log($action, $subject, newValues: $values + [
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    // The automatic ID check only helps staff, so a queue problem must not break the submission.
    private function queueOcrCheck(IdentityVerification $verification): void
    {
        try {
            $job = ProcessIdentityOcr::dispatch($verification->id);

            if (config('queue.default') === 'sync') {
                $job->onConnection('database');
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    // A failed notification must not break the submission.
    private function notifyAdmins(User $user): void
    {
        try {
            $admins = User::where('role', 'admin')->get();

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
