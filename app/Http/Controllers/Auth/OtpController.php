<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
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
    private const MAX_VERIFY_ATTEMPTS = 5;      // wrong codes per target, per 10 minutes

    private const RESEND_COOLDOWN = 120;        // seconds between resends

    private const MAX_RESENDS_PER_TARGET = 5;   // per hour, email and SMS

    private const MAX_RESENDS_PER_IP = 10;      // per hour

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

        $key = 'otp-verify:'.sha1($otp['to']);

        if (RateLimiter::tooManyAttempts($key, self::MAX_VERIFY_ATTEMPTS)) {
            $this->audit('otp.throttled', $otp);

            throw ValidationException::withMessages(['otp' => 'Too many attempts. Please request a new code later.']);
        }

        RateLimiter::hit($key, 600);

        if (! $this->otps->matches($otp, $data['otp'])) {
            $this->audit('otp.failed', $otp);

            throw ValidationException::withMessages(['otp' => 'The verification code is invalid or expired.']);
        }

        RateLimiter::clear($key);

        return match ($otp['purpose'] ?? null) {
            'register' => $this->finishRegister($request, $otp),
            'login' => $this->finishLogin($request, $otp),
            'reset' => $this->finishReset($request, $otp),
            default => $this->expired(),
        };
    }

    public function resend(Request $request)
    {
        $otp = $request->session()->get('otp');

        if (! $otp) {
            return $this->expired();
        }

        $target = sha1($otp['to']);
        $cooldownKey = 'otp-resend:'.$target;
        $targetKey = 'otp-resend-hour|'.$target;
        $ipKey = 'otp-resend-ip|'.$request->ip();

        // One resend every 120 seconds.
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return back()->withErrors(['otp' => 'Please wait before requesting another code.']);
        }

        // Hourly caps for email and SMS alike, so nobody can flood a victim's inbox or phone.
        if (
            RateLimiter::tooManyAttempts($targetKey, self::MAX_RESENDS_PER_TARGET)
            || RateLimiter::tooManyAttempts($ipKey, self::MAX_RESENDS_PER_IP)
        ) {
            $this->audit('otp.resend_throttled', $otp);

            return back()->withErrors(['otp' => 'Too many code requests. Please try again later.']);
        }

        $request->session()->put('otp', $this->otps->send($otp));

        RateLimiter::hit($cooldownKey, self::RESEND_COOLDOWN);
        RateLimiter::hit($targetKey, 3600);
        RateLimiter::hit($ipKey, 3600);
        RateLimiter::clear('otp-verify:'.$target);

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

        $remember = (bool) ($request->session()->get('login_pending')['remember'] ?? false);

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
            $user = new User;

            // forceFill so "role" never has to be in $fillable.
            $user->forceFill([
                'name' => $otp['name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'password' => $payload['password'],
                'consent_given_at' => $payload['consent_given_at'],
                'privacy_policy_version' => $payload['privacy_policy_version'],
                'consent_ip' => $payload['consent_ip'],
                'role' => 'citizen',
                'email_verified_at' => $isSms ? null : now(),
                'phone_verified_at' => $isSms ? now() : null,
            ])->save();

            $user->citizenProfile()->create([]);

            return $user;
        });

        $request->session()->forget('otp');
        Auth::login($user);
        $request->session()->regenerate();

        $this->audit('auth.registered', $otp, [], $user);

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Your account has been verified successfully.');
    }

    private function finishLogin(Request $request, array $otp)
    {
        $user = User::find($otp['payload']['user_id']);

        // Re-check at the last moment: the account may have been suspended
        // or changed since the password step.
        if (
            ! $user
            || $user->status !== 'active'
            || ! in_array($user->role, ['admin', 'staff', 'citizen'], true)
        ) {
            $request->session()->forget('otp');
            $this->audit('auth.login_blocked', $otp, ['reason' => 'account_unavailable_at_otp'], $user);

            return $this->expired('Your account is not available. Please contact the administrator.');
        }

        // A correct email code also proves the email address is real.
        if ($otp['channel'] === 'email' && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($otp['channel'] === 'sms' && $otp['to'] === $user->phone && ! $user->phone_verified_at) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        // No long-lived remember-me cookie for privileged accounts.
        $remember = (bool) ($otp['payload']['remember'] ?? false) && $user->isCitizen();

        $request->session()->forget('otp');
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()
            ->route($user->isCitizen() ? 'citizen.dashboard' : 'admin.dashboard')
            ->with('success', 'You are now logged in successfully.');
    }

    private function finishReset(Request $request, array $otp)
    {
        $request->session()->forget('otp');
        $request->session()->put('sms_reset', [
            'user_id' => $otp['payload']['user_id'],
            'expires' => now()->addMinutes(10)->timestamp,
        ]);

        $this->audit('auth.password_reset_otp_verified', $otp);

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

    /**
     * Never put the code, the phone number or the email in the audit record.
     * Pass $user when the account is not yet in $otp['payload'] (for example, right after registration).
     */
    private function audit(string $action, array $otp, array $extra = [], ?User $user = null): void
    {
        try {
            $user ??= isset($otp['payload']['user_id']) ? User::find($otp['payload']['user_id']) : null;

            app(AuditLogger::class)->log($action, $user, newValues: $extra + [
                'purpose' => $otp['purpose'] ?? null,
                'channel' => $otp['channel'] ?? null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
