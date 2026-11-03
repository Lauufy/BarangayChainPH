<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, OtpService $otps)
    {
        // The form has two tabs: email / employee ID, or mobile number.
        $byPhone = $request->input('mode') === 'phone';
        $field = $byPhone ? 'phone' : 'identifier';

        if ($byPhone) {
            $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

            $data = $request->validate([
                'phone' => ['required', 'regex:/^\+639\d{9}$/'],
                'password' => ['required', 'string'],
            ], ['phone.regex' => 'Enter a valid mobile number.']);

            $user = User::where('phone', $data['phone'])->first();
        } else {
            $data = $request->validate([
                'identifier' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ]);

            $identifier = trim($data['identifier']);

            $employee = EmployeeProfile::with('user')
                ->where('employee_id', strtoupper($identifier))
                ->first();

            $user = $employee?->user ?? User::where('email', $identifier)->first();
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            $this->fail($field, 'The login details or password are incorrect.');
        }

        if (! $user->is_active) {
            $this->fail($field, 'Your account is inactive. Please contact the administrator.');
        }

        $request->session()->regenerate();
        $remember = $request->boolean('remember');

        // Staff and admins log in with the password only.
        if (in_array($user->role, ['admin', 'staff'], true)) {
            Auth::login($user, $remember);

            return redirect()->route('admin.dashboard')->with('success', 'You are now logged in successfully.');
        }

        if (! $user->isCitizen()) {
            $this->fail($field, 'This account cannot log in.');
        }

        if ($byPhone) {
            $request->session()->put('otp', $otps->startLogin($user, 'sms', $remember));

            return redirect()->route('verification.otp.show')
                ->with('success', 'We sent a verification code to '.$otps->mask('sms', $user->phone).'.');
        }

        // Email login still requires a verified email, or an available verified channel.
        $channels = $otps->channelsFor($user);

        if (! $channels) {
            $this->fail($field, 'No email or verified mobile number is saved for this account.');
        }

        if ($user->email && ! $user->email_verified_at) {
            $request->session()->put('otp', $otps->startLogin($user, 'email', $remember));

            return redirect()->route('verification.otp.show')
                ->with('success', 'We sent an email verification code to '.$otps->mask('email', $user->email).'.');
        }

        // Two ways to get the code: let the citizen choose.
        if (count($channels) > 1) {
            $request->session()->put('login_pending', [
                'user_id' => $user->id,
                'remember' => $remember,
                'expires' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('two-factor.choose');
        }

        // Only one way: send the code right away.
        $request->session()->put('otp', $otps->startLogin($user, array_key_first($channels), $remember));

        return redirect()->route('verification.otp.show')
            ->with('success', 'We sent a verification code to '.reset($channels).'.');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out successfully.');
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
