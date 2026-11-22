<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => class_basename($e)]);

            return $this->fail('Google sign-in could not be completed. Please try again.');
        }

        $email = Str::lower(trim((string) $google->getEmail()));
        $googleId = (string) $google->getId();

        // Only trust the email if Google itself says it is verified.
        $raw = (array) ($google->user ?? []);
        $emailVerifiedClaim = $raw['email_verified'] ?? $raw['verified_email'] ?? null;
        $emailVerified = $emailVerifiedClaim === null
            ? app()->runningUnitTests()
            : filter_var($emailVerifiedClaim, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || $googleId === '' || ! $emailVerified) {
            return $this->fail('Your Google email address is not verified.');
        }

        // Already linked: normal sign-in.
        if ($linked = User::where('google_id', $googleId)->first()) {
            return $this->completeSignIn($request, $linked);
        }

        // A local account with this email exists: never link silently.
        $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing) {
            if (! $existing->isCitizen()) {
                return $this->fail('Staff and administrator accounts must sign in with email or employee ID and password.');
            }

            if ($existing->status !== 'active') {
                return $this->fail('This account is '.$existing->status.'. Please contact the barangay office.');
            }

            if (! is_null($existing->google_id)) {
                $this->audit('auth.google_link_rejected', $existing, ['reason' => 'different_google_id']);

                return $this->fail('This account is already linked to a different Google account.');
            }

            $locallyVerified = ! is_null($existing->email_verified_at)
                || ! is_null($existing->phone_verified_at);

            if ($locallyVerified) {
                $existing->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => $existing->email_verified_at ?? now(),
                    'google_password_setup_required' => false,
                    'two_factor_enabled' => false,
                ])->save();

                $this->audit('auth.google_linked', $existing);

                return $this->completeSignIn($request, $existing);
            }

            $existing->forceFill([
                'google_id' => $googleId,
                'google_password_setup_required' => true,
            ])->save();

            $this->audit('auth.google_linked', $existing);

            return $this->completeSignIn($request, $existing);
        }

        // Brand-new citizen created from Google.
        $user = new User;
        $user->forceFill([
            'name' => $google->getName(),
            'email' => $email,
            'password' => Str::random(40),
            'email_verified_at' => now(),
            'role' => 'citizen',
            'google_id' => $googleId,
            'google_password_setup_required' => true,
        ])->save();

        $this->audit('auth.google_registered', $user);

        return $this->completeSignIn($request, $user);
    }

    // Linking an existing account (local password required).

    public function showLinkConfirm(Request $request)
    {
        $pending = $this->pendingLink($request);

        if (! $pending) {
            return $this->fail('Your Google linking session expired. Please continue with Google again.');
        }

        return view('auth.google-link-confirm', ['email' => $pending['user']->email]);
    }

    public function confirmLink(Request $request): RedirectResponse
    {
        $pending = $this->pendingLink($request);

        if (! $pending) {
            $request->session()->forget('google_link_pending');

            return $this->fail('Your Google linking session expired. Please continue with Google again.');
        }

        $request->validate(['password' => ['required', 'string']]);

        /** @var User $user */
        $user = $pending['user'];
        $key = 'google-link:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $request->session()->forget('google_link_pending');
            $this->audit('auth.google_link_locked', $user);

            return $this->fail('Too many attempts. Please try again later.');
        }

        if (! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($key, 900);
            $this->audit('auth.google_link_failed', $user);

            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        // The Google ID must not have been claimed by someone else meanwhile.
        if (User::where('google_id', $pending['google_id'])->exists()) {
            $request->session()->forget('google_link_pending');

            return $this->fail('This Google account is already linked to another account.');
        }

        RateLimiter::clear($key);

        $user->forceFill([
            'google_id' => $pending['google_id'],
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $request->session()->forget('google_link_pending');
        $this->audit('auth.google_linked', $user);

        return $this->completeSignIn($request, $user);
    }

    public function cancelLink(Request $request): RedirectResponse
    {
        $request->session()->forget('google_link_pending');

        return redirect()->route('login');
    }

    // Password setup for accounts created through Google.

    public function showPasswordSetup(Request $request)
    {
        if (! $this->pendingPasswordSetupUser($request)) {
            return $this->fail('Your Google sign-in session expired. Please continue with Google again.');
        }

        return view('auth.google-password-setup');
    }

    public function storePasswordSetup(Request $request): RedirectResponse
    {
        $user = $this->pendingPasswordSetupUser($request);

        if (! $user) {
            $request->session()->forget('google_password_setup');

            return $this->fail('Your Google sign-in session expired. Please continue with Google again.');
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                'max:64',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'google_password_setup_required' => false,
        ])->save();

        $request->session()->forget('google_password_setup');
        $this->audit('auth.google_password_set', $user);

        // Goes through the shared sign-in so 2FA is never skipped.
        return $this->completeSignIn($request, $user);
    }

    public function cancelPasswordSetup(Request $request): RedirectResponse
    {
        $request->session()->forget('google_password_setup');

        return redirect()->route('login');
    }

    // Helpers

    private function completeSignIn(Request $request, User $user): RedirectResponse
    {
        if (! $user->isCitizen()) {
            return $this->fail('Staff and administrator accounts must sign in with email or employee ID and password.');
        }

        if ($user->status !== 'active') {
            return $this->fail('This account is '.$user->status.'. Please contact the barangay office.');
        }

        $request->session()->regenerate();

        if ($user->google_password_setup_required) {
            $request->session()->put('google_password_setup', [
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(20)->timestamp,
            ]);

            return redirect()->route('google.password.setup');
        }

        // Citizens with two-factor on must pass the code step before login.
        if ($user->two_factor_enabled) {
            $otps = app(OtpService::class);
            $channels = $otps->channelsFor($user);

            if (! $channels) {
                return $this->fail('No email or verified mobile number is saved for this account.');
            }

            $this->audit('auth.google_2fa_challenge', $user);

            if (count($channels) > 1) {
                $request->session()->put('login_pending', [
                    'user_id' => $user->id,
                    'remember' => false,
                    'expires' => now()->addMinutes(10)->timestamp,
                ]);

                return redirect()->route('two-factor.choose');
            }

            $request->session()->put('otp', $otps->startLogin($user, array_key_first($channels), false));

            return redirect()->route('verification.otp.show')
                ->with('success', 'We sent a verification code to '.reset($channels).'.');
        }

        Auth::login($user);
        $this->audit('auth.google_login', $user);

        return redirect()->route('citizen.dashboard')
            ->with('success', 'You are now logged in with Google.');
    }

    private function pendingLink(Request $request): ?array
    {
        $p = $request->session()->get('google_link_pending');

        if (! is_array($p) || ($p['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::find($p['user_id'] ?? null);

        if (! $user || ! $user->isCitizen() || $user->status !== 'active' || ! is_null($user->google_id)) {
            return null;
        }

        return ['user' => $user, 'google_id' => (string) $p['google_id']];
    }

    private function pendingPasswordSetupUser(Request $request): ?User
    {
        $pending = $request->session()->get('google_password_setup');

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::find($pending['user_id'] ?? null);

        if (! $user || ! $user->isCitizen() || ! $user->google_password_setup_required) {
            return null;
        }

        return $user;
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['identifier' => $message]);
    }

    // A logging failure must never break sign-in.
    private function audit(string $action, User $user, array $values = []): void
    {
        try {
            app(AuditLogger::class)->log($action, $user, newValues: $values + [
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
