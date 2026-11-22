<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIdentityOcr;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Notifications\CitizenSubmissionReceived;
use App\Services\AuditLogger;
use App\Services\SupabaseStorage;
use App\Support\Phone;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Manages citizen identity verification submissions and private uploads.
class IdentityVerificationController extends Controller
{
    public function __construct(private SupabaseStorage $supabaseStorage) {}

    private const MAX_REJECTED_TRIES = 3;

    // An ID number needs at least this many letters or digits (dashes and spaces do not count).
    private const MIN_ID_NUMBER_LENGTH = 5;

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

        return view('citizen.identity-verifications.index', [
            'user' => $user,
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

        $phone = $data['phone'];
        $email = $data['email'] ?? null;
        $phoneChanged = $user->phone !== $phone;
        unset($data['phone'], $data['email']);
        $data['address'] = implode(', ', array_filter([
            $data['house_number'] ?? null,
            $data['barangay'],
            $data['city_municipality'],
            $data['province'],
            $data['zip_code'],
        ]));

        DB::transaction(function () use ($user, $phone, $phoneChanged, $email, $data): void {
            if ($phoneChanged) {
                $user->update([
                    'phone' => $phone,
                    'phone_verified_at' => null,
                ]);
            }

            if ($email) {
                $user->update(['email' => $email]);
            }

            $user->citizenProfile()->updateOrCreate([], $data);
        });

        // The log says what happened, never the personal details themselves.
        $this->audit('identity.details_saved', $user, ['phone_changed' => $phoneChanged]);

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
            'selfie' => 'required|file|mimes:jpg,jpeg,png|max:5120',
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
        $selfie = $request->file('selfie');
        $documentPath = null;
        $selfiePath = null;

        try {
            $documentPath = $document->store("identity-documents/{$user->id}", 'supabase');
            $selfiePath = $selfie->store("identity-selfies/{$user->id}", 'supabase');

            if (! $documentPath || ! $selfiePath) {
                throw new \RuntimeException('An identity file could not be stored.');
            }

            $verification = $user->identityVerifications()->create([
                'status' => IdentityVerification::PENDING,
                'document_type' => $data['document_type'],
                'document_path' => $documentPath,
                'document_original_name' => $this->safeFileName($document->getClientOriginalName()),
                'id_number_hash' => $hash,
                'selfie_path' => $selfiePath,
                'selfie_source' => $data['selfie_source'],
                'consented_at' => now(),
                'submitted_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Do not leave half-saved ID files behind.
            $this->discardFiles([$documentPath, $selfiePath]);
            report($exception);

            return back()->withErrors([
                'document' => 'We could not save your files. Please try again.',
            ]);
        }

        $this->audit('identity.submitted', $verification, [
            'document_type' => $data['document_type'],
            'selfie_source' => $data['selfie_source'],
        ]);

        $this->queueOcrCheck($verification);

        $this->notifyAdmins($user);

        return $this->backToStatus()->with(
            'success',
            'Your application was submitted. Please wait for review.'
        );
    }

    // Allow citizens to open only their own uploaded ID.
    public function document(Request $request, IdentityVerification $verification): RedirectResponse
    {
        abort_unless($verification->user_id === $request->user()->id, 404);
        abort_if(blank($verification->document_path), 404);

        $this->audit('identity.document_viewed', $verification);

        return redirect()->away(
            $this->supabaseStorage->temporaryUrl($verification->document_path, now()->addMinutes(5)),
            302,
            ['Cache-Control' => 'private, no-store']
        );
    }

    // Validation rules for the ID details on submission.
    private function idRules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(self::DOCUMENT_TYPES)],
            'id_number' => [
                'bail',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9][A-Za-z0-9\-\/ ]*$/',
                // The duplicate check hashes only letters and digits, so that is what must be long enough.
                function (string $attribute, mixed $value, Closure $fail): void {
                    $clean = preg_replace('/[^A-Za-z0-9]/', '', (string) $value);

                    if (strlen($clean) < self::MIN_ID_NUMBER_LENGTH) {
                        $fail('The ID number is too short. Check it again.');
                    }
                },
            ],
        ];
    }

    private function idMessages(): array
    {
        return [
            'id_number.regex' => 'The ID number can only have letters, numbers, spaces, dashes, and slashes.',
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

    // The uploaded file name comes from the citizen, so clean it and keep it short.
    private function safeFileName(string $name): string
    {
        return Str::limit(preg_replace('/[^\w.\- ]/u', '_', basename($name)), 120, '');
    }

    // Delete uploaded files after a failed submission.
    private function discardFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (! $path) {
                continue;
            }

            try {
                Storage::disk('supabase')->delete($path);
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
            ProcessIdentityOcr::dispatch($verification->id);
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
