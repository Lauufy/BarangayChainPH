<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    // Send the user to Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Google sends the user back here
    public function callback()
    {
        $google = Socialite::driver('google')->user();

        // Find the user by email, or create a new one
        $user = User::firstOrCreate(
            ['email' => $google->getEmail()],
            [
                'name' => $google->getName(),
                'password' => Str::random(24),
                'email_verified_at' => now(),
            ]
        );

        // Save the Google ID
        $user->update(['google_id' => $google->getId()]);

        Auth::login($user);

        return redirect()->route('citizen.dashboard')->with('success', 'You are now logged in with Google.');
    }
}
