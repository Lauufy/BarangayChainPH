<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($data['identifier']);

        $employee = EmployeeProfile::with('user')
            ->where('employee_id', strtoupper($identifier))
            ->first();

        $email = $employee?->user?->email ?? $identifier;

        if (! Auth::attempt(['email' => $email, 'password' => $data['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'identifier' => 'The email/employee ID or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (! $user->is_active) {
            $this->fail($request, 'Your account is inactive. Please contact the administrator.');
        }

        if ($user->role === 'citizen' && ! $user->email_verified_at) {
            return $this->sendOtp($request, $user);
        }

        return match ($user->role) {
            'admin', 'staff' => redirect()->route('admin.dashboard'),
            'citizen' => redirect()->route('citizen.dashboard'),
            default => tap(redirect('/'), fn() => Auth::logout()),
        };
    }

    public function destroy(Request $request)
    {
        $this->logout($request);

        return redirect()->route('login');
    }

    private function sendOtp(Request $request, $user)
    {
        $smtp = config('mail.mailers.smtp', []);

        if (config('mail.default') === 'smtp' && (blank($smtp['host'] ?? null) || empty($smtp['port'] ?? null))) {
            Log::error('SMTP configuration is incomplete.', ['user_id' => $user->id]);

            $this->fail($request, 'Email verification is temporarily unavailable. Please contact the administrator.');
        }

        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_otp' => Hash::make($otp),
            'email_verification_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        try {
            Notification::route('mail', $user->email)
                ->notify(new EmailVerificationOtp($otp, $user->name));
        } catch (\Throwable $e) {
            report($e);

            $this->fail($request, 'We could not send your verification code. Please try again later.');
        }

        Auth::logout();
        $request->session()->regenerate();
        $request->session()->put(['verification_email' => $user->email, 'otp_flow' => 'login']);

        return redirect()->route('verification.otp.show')
            ->with('success', 'Your email is not verified. We sent you a new verification code.');
    }

    private function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function fail(Request $request, string $message): never
    {
        $this->logout($request);

        throw ValidationException::withMessages(['identifier' => $message]);
    }
}
