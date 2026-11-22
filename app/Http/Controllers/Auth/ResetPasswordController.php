<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordController extends Controller
{
    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
            'isSmsReset' => false,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => $this->passwordRules(),
        ]);

        $status = Password::reset(
            $validated,
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $this->finishReset($user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __($status),
                ]);
        }

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your password has been reset successfully. You can now sign in.'
            );
    }

    // Reset by SMS: the code was already verified, now choose a new password.
    public function showSms(Request $request)
    {
        return $this->smsResetUser($request)
            ? view('auth.reset-password', ['isSmsReset' => true])
            : $this->smsExpired();
    }

    public function resetSms(Request $request)
    {
        $user = $this->smsResetUser($request);

        if (! $user) {
            return $this->smsExpired();
        }

        $data = $request->validate(['password' => $this->passwordRules()]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->forget('sms_reset');
        event(new PasswordReset($user));
        $this->finishReset($user);

        return redirect()
            ->route('login')
            ->with('success', 'Your password has been reset successfully. You can now sign in.');
    }

    private function passwordRules(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            'max:16',
            PasswordRule::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
        ];
    }

    private function smsResetUser(Request $request): ?User
    {
        $reset = $request->session()->get('sms_reset');

        return $reset && $reset['expires'] >= now()->timestamp ? User::find($reset['user_id']) : null;
    }

    private function smsExpired()
    {
        return redirect()->route('password.request')
            ->withErrors(['phone' => 'Your reset session expired. Please start again.']);
    }

    private function finishReset(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();

        try {
            app(AuditLogger::class)->log('password_reset.completed', $user, newValues: [
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
