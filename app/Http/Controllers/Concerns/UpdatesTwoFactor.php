<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait UpdatesTwoFactor
{
    public function updateTwoFactor(Request $request)
    {
        $data = $request->validate([
            'two_factor_enabled' => ['required', 'boolean'],
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();
        $key = '2fa-change|'.$user->id.'|'.$request->ip();

        // A logging problem must never break this page (a closure avoids name clashes in controllers).
        $log = function (string $action, array $values = []) use ($user, $request): void {
            try {
                app(AuditLogger::class)->log($action, $user, newValues: $values + [
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        };

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'current_password' => 'Too many attempts. Please try again later.',
            ]);
        }

        if (! filled($user->password) || ! Hash::check($data['current_password'], $user->password)) {
            RateLimiter::hit($key, 900);
            $log('auth.2fa_change_failed');

            throw ValidationException::withMessages(['current_password' => 'The password is incorrect.']);
        }

        RateLimiter::clear($key);

        $enabled = (bool) $data['two_factor_enabled'];

        // Admin and staff must always use two-factor, same rule as the login page.
        if (! $enabled && in_array($user->role, ['admin', 'staff'], true)) {
            throw ValidationException::withMessages([
                'two_factor_enabled' => 'Two-factor login is required for admin and staff accounts.',
            ]);
        }

        // Don't let someone lock themselves out.
        if ($enabled && ! $user->email_verified_at && ! $user->phone_verified_at) {
            throw ValidationException::withMessages([
                'two_factor_enabled' => 'Verify your email or mobile number before turning this on.',
            ]);
        }

        if ((bool) $user->two_factor_enabled !== $enabled) {
            $user->forceFill(['two_factor_enabled' => $enabled])->save();
            $log($enabled ? 'auth.2fa_enabled' : 'auth.2fa_disabled');
        }

        return back()->with('success', $enabled
            ? 'Two-factor login is now on.'
            : 'Two-factor login is now off.');
    }
}
