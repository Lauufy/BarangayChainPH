<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    public function show(Request $request)
    {
        $registration = $this->pending($request, ['email', 'otp_hash']);

        if (! $registration) {
            return $this->expired();
        }

        return view('auth.verify-email', ['email' => $registration['email']]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);

        $registration = $this->pending($request, [
            'name', 'phone', 'email', 'password', 'otp_hash', 'otp_expires_at',
        ]);

        if (! $registration) {
            return $this->expired();
        }

        $email = $registration['email'];
        $key = 'email-otp:'.sha1(strtolower($email));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otp' => 'Too many attempts. Please wait before trying again.',
            ]);
        }

        RateLimiter::hit($key, 60);

        if (
            now()->timestamp > (int) $registration['otp_expires_at']
            || ! Hash::check($data['otp'], $registration['otp_hash'])
        ) {
            throw ValidationException::withMessages([
                'otp' => 'The verification code is invalid or expired.',
            ]);
        }

        if (User::where('email', $email)->exists()) {
            $this->clearPending($request);

            return $this->expired('This email is already registered. Please log in instead.');
        }

        // The citizen is only created after successful OTP verification.
        $user = DB::transaction(function () use ($registration, $email) {
            $user = User::create([
                'name' => $registration['name'],
                'email' => $email,
                'password' => $registration['password'],
                'role' => 'citizen',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->citizenProfile()->create(['phone' => $registration['phone']]);

            return $user;
        });

        RateLimiter::clear($key);
        $this->clearPending($request);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Your email has been verified successfully.');
    }

    public function resend(Request $request)
    {
        $registration = $this->pending($request, ['name', 'email', 'password']);

        if (! $registration) {
            return $this->expired();
        }

        $email = $registration['email'];

        if (User::where('email', $email)->exists()) {
            $this->clearPending($request);

            return redirect()->route('login')
                ->with('success', 'This email is already registered. Please log in.');
        }

        // One resend every 120 seconds.
        $key = 'email-otp-resend:'.sha1(strtolower($email));

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors(['otp' => 'Please wait before requesting another verification code.']);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            Notification::route('mail', $email)
                ->notify(new EmailVerificationOtp($otp, $registration['name']));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['otp' => 'We could not send a new code. Please try again later.']);
        }

        // Replace the stored OTP only after the email is sent.
        $registration['otp_hash'] = Hash::make($otp);
        $registration['otp_expires_at'] = now()->addMinutes(10)->timestamp;
        $registration['resend_count'] = ($registration['resend_count'] ?? 0) + 1;

        $request->session()->put('pending_registration', $registration);

        RateLimiter::hit($key, 120);

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    public function cancel(Request $request)
    {
        $this->clearPending($request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('register')
            ->with('success', 'Email verification was canceled.');
    }

    /** Returns the pending registration only if all required keys are filled. */
    private function pending(Request $request, array $required): ?array
    {
        $registration = $request->session()->get('pending_registration');

        if (! is_array($registration)) {
            return null;
        }

        foreach ($required as $field) {
            if (empty($registration[$field])) {
                return null;
            }
        }

        return $registration;
    }

    private function expired(string $message = 'Your verification session has expired. Please register again.')
    {
        return redirect()->route('register')->withErrors(['email' => $message]);
    }

    private function clearPending(Request $request): void
    {
        $request->session()->forget(['pending_registration', 'otp_flow']);
    }
}