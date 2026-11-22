<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Notifications\CitizenSubmissionReceived;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Lets a citizen ask to see, correct, or delete their personal data.
class PrivacyRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['access', 'correction', 'erasure'])],
            'details' => ['nullable', 'string', 'max:1000'],
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();
        $key = 'privacy-request|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'current_password' => 'Too many attempts. Please try again later.',
            ]);
        }

        // The password proves it is really the account owner asking.
        if (! filled($user->password) || ! Hash::check($data['current_password'], $user->password)) {
            RateLimiter::hit($key, 900);

            throw ValidationException::withMessages(['current_password' => 'The password is incorrect.']);
        }

        $alreadyOpen = DataSubjectRequest::where('user_id', $user->id)
            ->where('type', $data['type'])
            ->whereIn('status', ['pending', 'in_review'])
            ->exists();

        if ($alreadyOpen) {
            return back()->with('error', 'You already have an open request of this kind.');
        }

        $privacyRequest = DataSubjectRequest::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'details' => $data['details'] ?? null,
            'due_at' => now()->addDays(config('privacy.request_response_days')),
        ]);

        try {
            app(AuditLogger::class)->log('privacy.request_submitted', $user, newValues: [
                'request_id' => $privacyRequest->id,
                'type' => $data['type'],
                'ip_address' => $request->ip(),
            ]);

            $admins = User::where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CitizenSubmissionReceived(
                    'New privacy request',
                    "{$user->name} sent a {$data['type']} request about their personal data.",
                    route('admin.dashboard')
                ));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Your request was received. The barangay Data Protection Officer will answer within '
            .config('privacy.request_response_days').' days.');
    }
}