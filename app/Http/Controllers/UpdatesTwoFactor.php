<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The password is incorrect.']);
        }

        $enabled = (bool) $data['two_factor_enabled'];

        // Don't let someone lock themselves out.
        if ($enabled && ! $user->email_verified_at && ! $user->phone_verified_at) {
            throw ValidationException::withMessages([
                'two_factor_enabled' => 'Verify your email or mobile number before turning this on.',
            ]);
        }

        $user->forceFill(['two_factor_enabled' => $enabled])->save();

        // Log it with your existing audit helper, e.g.
        // AuditLogger::log($user, $enabled ? 'two_factor.enabled' : 'two_factor.disabled');

        return back()->with('success', $enabled
            ? 'Two-factor login is now on.'
            : 'Two-factor login is now off.');
    }
}
