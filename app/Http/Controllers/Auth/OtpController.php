<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * One code page for all three flows: register, login (2FA) and password reset by SMS.
 * The flow is stored in session('otp'). This replaces EmailVerificationController.
 */
class OtpController extends Controller
{
    public function __construct(private OtpService $otps) {}

    // Page where the user types the code
    public function show(Request $request)
    {
        $otp = $request->session()->get('otp');

        if (! $otp) {
            return $this->expired();
        }

        return view('auth.verify-otp', [
            'target' => $this->otps->mask($otp['channel'], $otp['to']),
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $otp = $request->session()->get('otp');

        if (! $otp) {
            return $this->expired();
        }

        // 5 tries per code
        $key = 'otp-verify:'.sha1($otp['to']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['otp' => 'Too many attempts. Please request a new code later.']);
        }

        RateLimiter::hit($key, 600);

        if (! $this->otps->matches($otp, $data['otp'])) {
            throw ValidationException::withMessages(['otp' => 'The verification code is invalid or expired.']);
        }

        RateLimiter::clear($key);

        return match ($otp['purpose']) {
            'register' => $this->finishRegister($request, $otp),
            'login' => $this->finishLogin($request, $otp),
            'reset' => $this->finishReset($request, $otp),
        };
    }

    public function resend(Request $request)
    {
        $otp = $request->session()->get('otp');

        if (! $otp) {
            return $this->expired();
        }

        // One resend every 120 seconds.
        $key = 'otp-resend:'.sha1($otp['to']);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors(['otp' => 'Please wait before requesting another code.']);
        }

        $request->session()->put('otp', $this->otps->send($otp));

        RateLimiter::hit($key, 120);
        RateLimiter::clear('otp-verify:'.sha1($otp['to']));

        return back()->with('success', 'A new code has been sent.');
    }

    public function cancel(Request $request)
    {
        $request->session()->forget(['otp', 'login_pending']);

        return redirect()->route('login')->with('success', 'Verification was canceled.');
    }

    // Login 2FA: page where the citizen picks email or SMS
    public function choose(Request $request)
    {
        $user = $this->pendingLoginUser($request);

        if (! $user) {
            return $this->expired();
        }

        return view('auth.two-factor', ['channels' => $this->otps->channelsFor($user)]);
    }

    public function sendLogin(Request $request)
    {
        $user = $this->pendingLoginUser($request);
        $channel = (string) $request->input('channel');

        if (! $user || ! array_key_exists($channel, $this->otps->channelsFor($user))) {
            return $this->expired();
        }

        $remember = $request->session()->get('login_pending')['remember'];

        $request->session()->put('otp', $this->otps->startLogin($user, $channel, $remember));
        $request->session()->forget('login_pending');

        return redirect()->route('verification.otp.show');
    }

    private function finishRegister(Request $request, array $otp)
    {
        $isSms = $otp['channel'] === 'sms';
        $payload = $otp['payload'];

        // Someone may have registered this email or phone while the code was pending.
        if (User::where($isSms ? 'phone' : 'email', $otp['to'])->exists()) {
            $request->session()->forget('otp');

            return $this->expired('This '.($isSms ? 'mobile number' : 'email').' is already registered. Please log in instead.');
        }

        // The citizen is only created after the code is correct.
        $user = DB::transaction(function () use ($payload, $isSms, $otp) {
            $user = User::create([
                'name' => $otp['name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'password' => $payload['password'],
                'role' => 'citizen',
                'is_active' => true,
                'email_verified_at' => $isSms ? null : now(),
                'phone_verified_at' => $isSms ? now() : null,
            ]);

            $user->citizenProfile()->create([]);

            return $user;
        });

        $request->session()->forget('otp');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Your account has been verified successfully.');
    }

    private function finishLogin(Request $request, array $otp)
    {
        $user = User::find($otp['payload']['user_id']);

        if (! $user || ! $user->is_active) {
            return $this->expired('Your account is not available. Please contact the administrator.');
        }

        // A correct email code also proves the email address is real.
        if ($otp['channel'] === 'email' && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($otp['channel'] === 'sms' && $otp['to'] === $user->phone && ! $user->phone_verified_at) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        $request->session()->forget('otp');
        Auth::login($user, $otp['payload']['remember']);
        $request->session()->regenerate();

        return redirect()->route('citizen.dashboard')
            ->with('success', 'You are now logged in successfully.');
    }

    private function finishReset(Request $request, array $otp)
    {
        $request->session()->forget('otp');
        $request->session()->put('sms_reset', [
            'user_id' => $otp['payload']['user_id'],
            'expires' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->route('password.sms.show');
    }

    private function pendingLoginUser(Request $request): ?User
    {
        $pending = $request->session()->get('login_pending');

        return $pending && $pending['expires'] >= now()->timestamp ? User::find($pending['user_id']) : null;
    }

    private function expired(string $message = 'Your verification session has expired. Please try again.')
    {
        return redirect()->route('login')->withErrors(['otp' => $message]);
    }
}
