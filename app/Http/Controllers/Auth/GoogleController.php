<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    // Send the user to Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Google sends the user back here
    public function callback(Request $request): RedirectResponse
    {
        $google = Socialite::driver('google')->user();

        // Look up the account BEFORE we create it or mark anything as verified.
        // Google sign-in always stamps email_verified_at, so checking later
        // would always come back true.
        $existingUser = User::where('email', $google->getEmail())->first();
        $alreadyHadVerifiedContact = $existingUser && $this->hasVerifiedContact($existingUser);
        $hadNoGoogleLinkYet = $existingUser && is_null($existingUser->google_id);

        // Find the user by email, or create a new citizen
        $user = User::firstOrCreate(
            ['email' => $google->getEmail()],
            [
                'name' => $google->getName(),
                'password' => Str::random(24),
                'email_verified_at' => now(),
                'role' => 'citizen',
                'is_active' => true,
            ]
        );

        if ($user->wasRecentlyCreated) {
            // Brand-new Google account: the user never chose a password.
            $user->forceFill(['google_password_setup_required' => true])->save();
        } elseif (
            $alreadyHadVerifiedContact
            && $hadNoGoogleLinkYet
            && $user->google_password_setup_required
        ) {
            // Existing account (registered normally, never used Google before)
            // with a verified email or phone: assume they already have a password.
            $user->forceFill(['google_password_setup_required' => false])->save();
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Your account is inactive. Please contact the administrator.',
            ]);
        }

        // Staff and administrators must use the password login.
        if (! $user->isCitizen()) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Staff and administrator accounts must sign in with email or employee ID and password.',
            ]);
        }

        // Completing Google sign-in verifies the email address returned by Google.
        $user->forceFill([
            'google_id' => $google->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        if ($user->google_password_setup_required) {
            $request->session()->regenerate();
            $request->session()->put('google_password_setup', [
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(20)->timestamp,
            ]);

            return redirect()->route('google.password.setup');
        }

        Auth::login($user);

        return redirect()->route('citizen.dashboard')->with('success', 'You are now logged in with Google.');
    }

    public function showPasswordSetup(Request $request)
    {
        if (! $this->pendingPasswordSetupUser($request)) {
            return redirect()->route('login')->withErrors([
                'password' => 'Your Google sign-in session expired. Please continue with Google again.',
            ]);
        }

        return view('auth.google-password-setup');
    }

    public function storePasswordSetup(Request $request): RedirectResponse
    {
        $user = $this->pendingPasswordSetupUser($request);

        if (! $user) {
            $request->session()->forget('google_password_setup');

            return redirect()->route('login')->withErrors([
                'password' => 'Your Google sign-in session expired. Please continue with Google again.',
            ]);
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                'max:16',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'google_password_setup_required' => false,
        ])->save();

        $request->session()->forget('google_password_setup');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('citizen.dashboard')
            ->with('success', 'Your password is set and you are now logged in.');
    }

    /**
     * An account that already had a verified email or phone before this Google
     * sign-in was created through normal registration, so we assume it already
     * has a password. Google never shares its password with us, so the
     * "set a password" step is only needed for accounts created by Google itself.
     */
    private function hasVerifiedContact(User $user): bool
    {
        return ! is_null($user->email_verified_at)
            || ! is_null($user->phone_verified_at);
    }

    private function pendingPasswordSetupUser(Request $request): ?User
    {
        $pending = $request->session()->get('google_password_setup');

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        $user = User::find($pending['user_id'] ?? null);

        if (! $user || ! $user->is_active || ! $user->isCitizen() || ! $user->google_password_setup_required) {
            return null;
        }

        return $user;
    }
}