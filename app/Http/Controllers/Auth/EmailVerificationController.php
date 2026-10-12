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
        $registration = $request->session()->get('pending_registration');

        if (
            ! is_array($registration) ||
            empty($registration['email']) ||
            empty($registration['otp_hash'])
        ) {
            return redirect()->route('register')
                ->withErrors([
                    'email' => 'Your verification session has expired. Please register again.',
                ]);
        }

        return view('auth.verify-email', [
            'email' => $registration['email'],
        ]);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $registration = $request->session()
            ->get('pending_registration');

        if (
            ! is_array($registration) ||
            empty($registration['name']) ||
            empty($registration['phone']) ||
            empty($registration['email']) ||
            empty($registration['password']) ||
            empty($registration['otp_hash']) ||
            empty($registration['otp_expires_at'])
        ) {
            return redirect()->route('register')
                ->withErrors([
                    'email' => 'Your verification session has expired. Please register again.',
                ]);
        }

        $email = $registration['email'];

        // Rate limit verification attempts by email.
        $key = 'email-otp:'.sha1(strtolower($email));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otp' => 'Too many attempts. Please wait before trying again.',
            ]);
        }

        RateLimiter::hit($key, 60);

        // Check OTP expiration and validity.
        if (
            now()->timestamp > (int) $registration['otp_expires_at'] ||
            ! Hash::check(
                $validated['otp'],
                $registration['otp_hash']
            )
        ) {
            throw ValidationException::withMessages([
                'otp' => 'The verification code is invalid or expired.',
            ]);
        }

        // Prevent duplicate accounts.
        if (User::where('email', $email)->exists()) {
            $request->session()->forget([
                'pending_registration',
                'otp_flow',
            ]);

            return redirect()->route('register')
                ->withErrors([
                    'email' => 'This email is already registered. Please log in instead.',
                ]);
        }

        // Create the citizen only after successful OTP verification.
        $user = DB::transaction(function () use ($registration, $email) {
            $user = User::create([
                'name' => $registration['name'],
                'email' => $email,
                'password' => $registration['password'],
                'role' => 'citizen',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->citizenProfile()->create([
                'phone' => $registration['phone'],
            ]);

            return $user;
        });

        RateLimiter::clear($key);

        // Clear temporary registration data.
        $request->session()->forget([
            'pending_registration',
            'otp_flow',
        ]);

        // Log in the newly verified citizen.
        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('citizen.dashboard')
            ->with(
                'success',
                'Your email has been verified successfully.'
            );
    }

    public function resend(Request $request)
    {
        $registration = $request->session()
            ->get('pending_registration');

        if (
            ! is_array($registration) ||
            empty($registration['name']) ||
            empty($registration['email']) ||
            empty($registration['password'])
        ) {
            return redirect()->route('register')
                ->withErrors([
                    'email' => 'Your verification session has expired. Please register again.',
                ]);
        }

        $email = $registration['email'];

        // Do not send a new OTP for an already registered email.
        if (User::where('email', $email)->exists()) {
            $request->session()->forget([
                'pending_registration',
                'otp_flow',
            ]);

            return redirect()->route('login')
                ->with(
                    'success',
                    'This email is already registered. Please log in.'
                );
        }

        // Allow one resend every 120 seconds.
        $key = 'email-otp-resend:'.sha1(strtolower($email));

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors([
                'otp' => 'Please wait before requesting another verification code.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            Notification::route('mail', $email)
                ->notify(
                    new EmailVerificationOtp(
                        $otp,
                        $registration['name']
                    )
                );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'otp' => 'We could not send a new code. Please try again later.',
            ]);
        }

        // Replace the stored OTP only after the email is sent.
        $registration['otp_hash'] = Hash::make($otp);
        $registration['otp_expires_at'] = now()
            ->addMinutes(10)
            ->timestamp;

        $registration['resend_count'] =
            ($registration['resend_count'] ?? 0) + 1;

        $request->session()->put(
            'pending_registration',
            $registration
        );

        RateLimiter::hit($key, 120);

        return back()->with(
            'success',
            'A new verification code has been sent to your email.'
        );
    }

    public function cancel(Request $request)
    {
        $request->session()->forget([
            'pending_registration',
            'otp_flow',
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('register')
            ->with('success', 'Email verification was canceled.');
    }
}
