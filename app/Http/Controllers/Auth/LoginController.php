<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 3;      // wrong attempts per account + IP

    private const MAX_IP_ATTEMPTS = 20;  // wrong attempts per IP, any account

    private const LOCK_SECONDS = 300;    // 5 minutes

    private const STAFF_2FA_REQUIRED = false;

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, OtpService $otps)
    {
        $byPhone = $request->input('mode') === 'phone';
        $field = $byPhone ? 'phone' : 'identifier';

        if ($byPhone) {
            $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

            $data = $request->validate([
                'phone' => ['required', 'regex:/^\+639\d{9}$/'],
                'password' => ['required', 'string'],
            ], ['phone.regex' => 'Enter a valid mobile number.']);
        } else {
            $data = $request->validate([
                'identifier' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ]);
        }

        $ip = $request->ip();
        $normalized = Str::lower(trim($data[$field]));
        $accountKey = 'login|'.sha1($normalized).'|'.$ip;
        $ipKey = 'login-ip|'.$ip;

        if ($locked = $this->lockedFor($accountKey, $ipKey)) {
            $this->lockout($locked);
        }

        if ($byPhone) {
            $user = User::where('phone', $data['phone'])->first();
        } else {
            $identifier = trim($data['identifier']);

            $employee = EmployeeProfile::with('user')
                ->where('employee_id', strtoupper($identifier))
                ->first();

            $user = $employee?->user
                ?? User::whereRaw('LOWER(email) = ?', [Str::lower($identifier)])->first();
        }

        if ($user && filled($user->password)) {
            $passwordOk = Hash::check($data['password'], $user->password);
        } else {
            // Spend the same time as a real check so response time doesn't reveal the account.
            Hash::make(Str::random(16));
            $passwordOk = false;
        }

        $idLog = [
            'identifier_type' => $byPhone ? 'phone' : 'email_or_employee_id',
            // Keyed hash, never the typed identifier itself.
            'identifier_ref' => substr(hash_hmac('sha256', $normalized, (string) config('app.key')), 0, 16),
        ];

        if (! $passwordOk) {
            RateLimiter::hit($accountKey, self::LOCK_SECONDS);
            RateLimiter::hit($ipKey, self::LOCK_SECONDS);

            if ($locked = $this->lockedFor($accountKey, $ipKey)) {
                $this->audit('auth.lockout', $user, $idLog + ['seconds' => $locked]);
                $this->lockout($locked);
            }

            $this->audit('auth.login_failed', $user, $idLog);

            $left = RateLimiter::remaining($accountKey, self::MAX_ATTEMPTS);

            $this->fail(
                $field,
                'Incorrect login details or password. '.$left.' '.Str::plural('attempt', $left).' left.'
            );
        }

        RateLimiter::clear($accountKey);
        $request->session()->regenerate();

        if (! in_array($user->role, ['admin', 'staff', 'citizen'], true)) {
            $this->audit('auth.login_blocked', $user, ['reason' => 'invalid_role']);
            $this->fail($field, 'This account cannot log in.');
        }

        if ($user->status !== 'active') {
            $this->audit('auth.login_blocked', $user, ['reason' => 'status_'.$user->status]);
            $this->fail($field, 'This account is '.$user->status.'. Please contact the barangay office.');
        }

        $isStaff = in_array($user->role, ['admin', 'staff'], true);

        // No long-lived "remember me" cookie for privileged accounts.
        $remember = $request->boolean('remember') && ! $isStaff;

        if ($byPhone && ! $user->phone_verified_at) {
            $request->session()->put('otp', $otps->startLogin($user, 'sms', $remember));

            return redirect()->route('verification.otp.show')
                ->with('success', 'We sent a verification code to '.$otps->mask('sms', $user->phone).'.');
        }

        $needsTwoFactor = $user->two_factor_enabled || (self::STAFF_2FA_REQUIRED && $isStaff);

        if (! $needsTwoFactor) {
            Auth::login($user, $remember);

            return redirect()
                ->route($user->role === 'citizen' ? 'citizen.dashboard' : 'admin.dashboard')
                ->with('success', 'You are now logged in successfully.');
        }

        if ($byPhone) {
            $request->session()->put('otp', $otps->startLogin($user, 'sms', $remember));

            return redirect()->route('verification.otp.show')
                ->with('success', 'We sent a verification code to '.$otps->mask('sms', $user->phone).'.');
        }

        $channels = $otps->channelsFor($user);

        if (! $channels) {
            $this->fail('password', 'No email or verified mobile number is saved for this account.');
        }

        if ($user->email && ! $user->email_verified_at) {
            $request->session()->put('otp', $otps->startLogin($user, 'email', $remember));

            return redirect()->route('verification.otp.show')
                ->with('success', 'We sent an email verification code to '.$otps->mask('email', $user->email).'.');
        }

        if (count($channels) > 1) {
            $request->session()->put('login_pending', [
                'user_id' => $user->id,
                'remember' => $remember,
                'expires' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('two-factor.choose');
        }

        $request->session()->put('otp', $otps->startLogin($user, array_key_first($channels), $remember));

        return redirect()->route('verification.otp.show')
            ->with('success', 'We sent a verification code to '.reset($channels).'.');
    }

    private function audit(string $action, ?User $user, array $values = []): void
    {
        try {
            // Adjust to your AuditLogger::log() signature. It must accept a null subject.
            app(AuditLogger::class)->log($action, $user, newValues: $values + ['ip_address' => request()->ip()]);
        } catch (\Throwable $e) {
            report($e); // A logging failure must never reveal whether an account exists.
        }
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'You have been logged out successfully.')
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => 'Sat, 01 Jan 1990 00:00:00 GMT',
            ]);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    /** Seconds remaining on whichever limiter is locked, or 0 if neither is. */
    private function lockedFor(string $accountKey, string $ipKey): int
    {
        $seconds = 0;

        if (RateLimiter::tooManyAttempts($accountKey, self::MAX_ATTEMPTS)) {
            $seconds = max($seconds, RateLimiter::availableIn($accountKey));
        }

        if (RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS)) {
            $seconds = max($seconds, RateLimiter::availableIn($ipKey));
        }

        return $seconds;
    }

    private function lockout(int $seconds): never
    {
        // The login page reads this to disable the button and show the countdown.
        session()->flash('lockout_seconds', $seconds);

        $minutes = (int) ceil($seconds / 60);

        // 'login' is not tied to any input, so it shows only in the general error box.
        throw ValidationException::withMessages([
            'login' => 'Too many failed attempts. Please try again in '.$minutes.' '.Str::plural('minute', $minutes).'.',
        ]);
    }
}
