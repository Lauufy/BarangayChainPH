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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required', 'string', 'confirmed', 'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'terms' => ['required', 'accepted'],
            'g-recaptcha-response' => ['required', 'string'],
        ], [
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
            'g-recaptcha-response.required' => 'Please complete the CAPTCHA verification.',
        ]);

        $this->verifyCaptcha($request, $data['g-recaptcha-response']);

        $otp = (string) random_int(100000, 999999);

        // No user is created yet; details are held in the session until the OTP is verified.
        $request->session()->regenerate();
        $request->session()->put([
            'otp_flow' => 'register',
            'pending_registration' => [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'otp_hash' => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(10)->timestamp,
                'resend_count' => 0,
            ],
        ]);

        try {
            Notification::route('mail', $data['email'])
                ->notify(new EmailVerificationOtp($otp, $data['name']));
        } catch (\Throwable $e) {
            report($e);

            $request->session()->forget(['pending_registration', 'otp_flow']);

            throw ValidationException::withMessages([
                'email' => 'We could not send your verification code. Please check your email settings and try again.',
            ]);
        }

        return redirect()
            ->route('verification.otp.show')
            ->with('success', 'Registration successful. We sent a verification code to your email.');
    }

    private function verifyCaptcha(Request $request, string $token): void
    {
        $secret = config('services.recaptcha.secret_key');

        if (blank($secret)) {
            Log::error('Registration CAPTCHA failed: secret key is missing.');
            $this->captchaError('CAPTCHA verification is temporarily unavailable. Please try again.');
        }

        try {
            $response = Http::asForm()->timeout(10)->post(
                'https://www.google.com/recaptcha/api/siteverify',
                ['secret' => $secret, 'response' => $token, 'remoteip' => $request->ip()]
            );
        } catch (ConnectionException $e) {
            Log::error('Registration CAPTCHA connection failed.', ['exception' => $e->getMessage()]);
            $this->captchaError('CAPTCHA verification is temporarily unavailable. Please try again.');
        }

        if (! $response->successful() || ! $response->json('success', false)) {
            Log::warning('Registration CAPTCHA unsuccessful.', [
                'http_status' => $response->status(),
                'error_codes' => $response->json('error-codes', []),
            ]);
            $this->captchaError('CAPTCHA verification failed. Please try again.');
        }
    }

    private function captchaError(string $message): never
    {
        throw ValidationException::withMessages(['g-recaptcha-response' => $message]);
    }
}