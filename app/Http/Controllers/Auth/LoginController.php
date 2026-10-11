<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please contact the administrator.',
            ]);
        }

        // Only citizens must verify their email through OTP.
        if ($user->role === 'citizen' && ! $user->email_verified_at) {
            $otp = (string) random_int(100000, 999999);

            $user->forceFill([
                'email_verification_otp' => Hash::make($otp),
                'email_verification_otp_expires_at' => now()->addMinutes(10),
            ])->save();

            try {
                Notification::route('mail', $user->email)
                    ->notify(new EmailVerificationOtp($otp, $user->name));
            } catch (\Throwable $exception) {
                report($exception);

                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'We could not send your verification code. Please try again later.',
                ]);
            }

            // Keep the verification session, but don't leave the citizen logged in.
            Auth::logout();

            $request->session()->regenerate();
            $request->session()->put([
                'verification_email' => $user->email,
                'otp_flow' => 'login',
            ]);

            return redirect()->route('verification.otp.show')
                ->with('success', 'Your email is not verified. We sent you a new verification code.');
        }

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'staff' => redirect()->route('staff.dashboard'),
            'citizen' => redirect()->route('citizen.dashboard'),
            default => tap(redirect('/'), fn () => Auth::logout()),
        };
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
