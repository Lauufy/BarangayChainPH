<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Support\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request, OtpService $otps)
    {
        // The form has two tabs: sign up with an email or with a mobile number.
        $byPhone = $request->input('mode') === 'phone';

        // Only the tab the user picked is required.
        if ($byPhone) {
            // Save phone numbers in one format: +639XXXXXXXXX
            $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

            $contact = ['phone' => ['required', 'regex:/^\+639\d{9}$/', 'unique:users,phone']];
        } else {
            $contact = ['email' => ['required', 'string', 'email', 'max:255', 'unique:users,email']];
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => [
                'required', 'string', 'confirmed', 'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'terms' => ['required', 'accepted'],
            'g-recaptcha-response' => ['required', 'string'],
        ] + $contact, [
            'phone.regex' => 'Enter a valid mobile number.',
            'phone.unique' => 'This mobile number is already registered.',
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
            'g-recaptcha-response.required' => 'Please complete the CAPTCHA verification.',
        ]);

        $this->verifyCaptcha($request, $data['g-recaptcha-response']);

        // No user is created yet; details are held in the session until the code is verified.
        $otp = $otps->start(
            'register',
            $byPhone ? 'sms' : 'email',
            $byPhone ? $data['phone'] : $data['email'],
            $data['name'],
            [
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'consent_given_at' => now()->toIso8601String(),
                'privacy_policy_version' => config('privacy.policy_version'),
                'consent_ip' => $request->ip(),
            ]
        );

        $request->session()->regenerate();
        $request->session()->put('otp', $otp);

        return redirect()
            ->route('verification.otp.show')
            ->with('success', 'We sent a verification code to your '.($byPhone ? 'mobile number.' : 'email.'));
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
