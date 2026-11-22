<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ===== Guest routes (logged-out users only) =====

Route::middleware(['guest', 'nocache'])->group(function () {

    // Show the registration form.
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    // Handle registration, limited to 10 attempts per minute.
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:10,1,registration')
        ->name('register.store');

    // Show the login form.
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    // Handle login, limited to 10 attempts per minute.
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    // Show the "forgot password" form (email reset).
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPassword'])
        ->name('password.request');

    // Send the password reset link by email, limited to 3 per minute.
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    // Show the reset form opened from the emailed link.
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetPassword'])
        ->name('password.reset');

    // Save the new password from the email reset, limited to 5 per minute.
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    // Show the SMS password reset form.
    Route::get('/reset-password-sms', [ResetPasswordController::class, 'showSms'])
        ->name('password.sms.show');

    // Save the new password from the SMS reset, limited to 5 per minute.
    Route::post('/reset-password-sms', [ResetPasswordController::class, 'resetSms'])
        ->middleware('throttle:5,1')
        ->name('password.sms.update');

    // Send the user to Google to sign in.
    Route::get('/auth/google', [GoogleController::class, 'redirect'])
        ->name('google.redirect');

    // Handle the response coming back from Google, limited to 20 per minute.
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('google.callback');
});

// ===== Sensitive auth pages (never cached by the browser) =====

Route::middleware('nocache')->group(function () {

    // ----- Google: link an existing account (local password required) -----

    // Show the "confirm your password to link Google" page.
    Route::get('/auth/google/link', [GoogleController::class, 'showLinkConfirm'])
        ->name('google.link.confirm');

    // Verify the password and link the Google account, limited to 10 per minute.
    Route::post('/auth/google/link', [GoogleController::class, 'confirmLink'])
        ->middleware('throttle:10,1')
        ->name('google.link.confirm.post');

    // Cancel linking and clear the pending session data.
    Route::post('/auth/google/link/cancel', [GoogleController::class, 'cancelLink'])
        ->name('google.link.cancel');

    // ----- Google: password setup for accounts created through Google -----

    // Show the password setup form for users who signed up with Google.
    Route::get('/auth/google/password', [GoogleController::class, 'showPasswordSetup'])
        ->name('google.password.setup');

    // Save the password for a Google user, limited to 5 per minute.
    Route::put('/auth/google/password', [GoogleController::class, 'storePasswordSetup'])
        ->middleware('throttle:5,1')
        ->name('google.password.setup.store');

    // Cancel password setup and clear the pending session data.
    Route::post('/auth/google/password/cancel', [GoogleController::class, 'cancelPasswordSetup'])
        ->name('google.password.cancel');

    // ----- Email verification and login two-factor (OTP) -----

    // Show the OTP entry page.
    Route::get('/email/verify', [OtpController::class, 'show'])
        ->name('verification.otp.show');

    // Check the submitted OTP code, limited to 10 attempts per minute.
    Route::post('/email/verify', [OtpController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('verification.otp.verify');

    // Resend the OTP code, limited to 3 per minute.
    Route::post('/email/verification/resend', [OtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.otp.resend');

    // Cancel the verification process.
    Route::post('/email/verification/cancel', [OtpController::class, 'cancel'])
        ->name('verification.cancel');

    // Let the user choose email or SMS for login two-factor.
    Route::get('/two-factor', [OtpController::class, 'choose'])
        ->name('two-factor.choose');

    // Send the login two-factor code, limited to 3 per minute.
    Route::post('/two-factor', [OtpController::class, 'sendLogin'])
        ->middleware('throttle:3,1')
        ->name('two-factor.send');
});
