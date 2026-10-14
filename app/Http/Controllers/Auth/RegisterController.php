<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        // Validate registration details.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'max:16',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'terms' => ['required', 'accepted'],
            'g-recaptcha-response' => ['required', 'string'],
        ], [
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
            'g-recaptcha-response.required' => 'Please complete the CAPTCHA verification.',
        ]);

        // Verify CAPTCHA configuration.
        $captchaToken = $validated['g-recaptcha-response'];
        $secretKey = config('services.recaptcha.secret_key');

        if (empty($secretKey)) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'CAPTCHA verification is temporarily unavailable. Please try again.',
            ]);
        }

        // Verify CAPTCHA with Google.
        try {
            $captchaResponse = Http::asForm()
                ->timeout(10)
                ->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    [
                        'secret' => $secretKey,
                        'response' => $captchaToken,
                        'remoteip' => $request->ip(),
                    ]
                );
        } catch (ConnectionException $exception) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'CAPTCHA verification is temporarily unavailable. Please try again.',
            ]);
        }

        if (
            ! $captchaResponse->successful() ||
            ! $captchaResponse->json('success', false)
        ) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'CAPTCHA verification failed. Please try again.',
            ]);
        }

        if (
            config('mail.default') !== 'resend' ||
            blank(config('services.resend.key')) ||
            blank(config('mail.from.address'))
        ) {
            Log::error('Unable to send registration verification code because Resend is not configured.', [
                'mailer' => config('mail.default'),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Email verification is temporarily unavailable. Please contact the administrator.',
            ]);
        }

        // Generate a six-digit OTP.
        $otp = (string) random_int(100000, 999999);

        // Store registration details temporarily.
        // No user is created in the database at this stage.
        $request->session()->regenerate();

        $request->session()->put('otp_flow', 'register');

        $request->session()->put('pending_registration', [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'otp_hash' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10)->timestamp,
            'resend_count' => 0,
        ]);

        // Send the OTP to the provided email address.
        try {
            Notification::route('mail', $validated['email'])
                ->notify(
                    new EmailVerificationOtp(
                        $otp,
                        $validated['name']
                    )
                );
        } catch (\Throwable $exception) {
            report($exception);

            $request->session()->forget([
                'pending_registration',
                'otp_flow',
            ]);

            throw ValidationException::withMessages([
                'email' => 'We could not send your verification code. Please check your email settings and try again.',
            ]);
        }

        // Redirect to the existing OTP verification page.
        return redirect()
            ->route('verification.otp.show')
            ->with(
                'success',
                'Registration successful. We sent a verification code to your email.'
            );
    }
}
