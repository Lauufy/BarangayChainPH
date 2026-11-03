<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request, OtpService $otps)
    {
        // Mobile number tab: send a code by SMS instead of an email link.
        if ($request->input('mode') === 'phone') {
            return $this->sendSmsCode($request, $otps);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __($status),
                ]);
        }

        return back()->with(
            'success',
            'If an account matches that email, a password reset link will be sent.'
        );
    }

    private function sendSmsCode(Request $request, OtpService $otps)
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $data = $request->validate([
            'phone' => ['required', 'regex:/^\+639\d{9}$/'],
        ], ['phone.regex' => 'Enter a valid mobile number.']);

        // Only a verified number can be used to reset a password.
        $user = User::where('phone', $data['phone'])->whereNotNull('phone_verified_at')->first();

        if (! $user) {
            return back()->withInput()->withErrors(['phone' => 'No account uses this verified mobile number.']);
        }

        $request->session()->put('otp', $otps->start('reset', 'sms', $user->phone, $user->name, ['user_id' => $user->id]));

        return redirect()->route('verification.otp.show')
            ->with('success', 'We sent a code to your mobile number.');
    }
}
