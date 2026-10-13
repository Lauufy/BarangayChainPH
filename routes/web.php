<?php

use App\Http\Controllers\Admin\IdentityReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Citizen\IdentityVerificationController;
use App\Http\Controllers\Citizen\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisterController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'store'])
        ->name('register.store');

    // Login
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');

    // Forgot Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPassword'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    // Reset Password
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetPassword'])
        ->name('password.reset');

    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Email Verification Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Keep the named verification notice route without duplicating /email/verify.
    Route::view('/email/verification/notice', 'auth.verify-email')
        ->name('verification.notice');
});

Route::get('/email/verify', [EmailVerificationController::class, 'show'])
    ->name('verification.otp.show');

Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('verification.otp.verify');

Route::post('/email/verification/resend', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:3,1')
    ->name('verification.otp.resend');

Route::post('/email/verification/cancel', [EmailVerificationController::class, 'cancel'])
    ->name('verification.cancel');

/*
|--------------------------------------------------------------------------
| Citizen Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:citizen'])
    ->prefix('citizen')
    ->name('citizen.')
    ->group(function () {
        Route::view('/dashboard', 'citizen.dashboard')->name('dashboard');
        Route::view('/services', 'citizen.services')->name('services');
        Route::view('/documents', 'citizen.documents')->name('documents');
        Route::view('/applications', 'citizen.applications')->name('applications');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('/profile/photo', [ProfileController::class, 'showPhoto'])->name('profile.photo.show');

        // Digital Identity Verification
        Route::controller(IdentityVerificationController::class)->group(function () {
            Route::get('/identity-verification', 'show')
                ->name('identity-verification');

            Route::post('/identity-verification/details', 'storeDetails')
                ->name('identity-verification.details');

            Route::post('/identity-verification/document', 'storeDocument')
                ->middleware('throttle:10,1')
                ->name('identity-verification.document');

            Route::get('/identity-verification/document/{verification}', 'document')
                ->name('identity-verification.file');
        });

        // Dismiss a notification (only the user's own can be found)
        Route::post('/notifications/{id}/read', function (Request $request, string $id) {
            $request->user()->unreadNotifications()->find($id)?->markAsRead();

            return back();
        })->name('notifications.read');

        Route::middleware('identity.verified')->group(function () {
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');
        });
    });

/*
|--------------------------------------------------------------------------
| Staff Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::view('/staff/dashboard', 'staff.dashboard')
        ->name('staff.dashboard');
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard and Modules
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // User & Role Management
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');

        Route::post('/users', [UserController::class, 'store'])
            ->name('users.store');

        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');

        // Permit & Clearance Processing
        Route::view('/permits', 'admin.permits')
            ->name('permits.index');

        // Document Authentication
        Route::view('/document-authentication', 'admin.document-authentication')
            ->name('document-authentication.index');

        // Blockchain Transaction Ledger
        Route::view('/blockchain-ledger', 'admin.blockchain-ledger')
            ->name('blockchain-ledger.index');

        // Notifications & Messaging
        Route::view('/notifications', 'admin.notifications')
            ->name('notifications.index');

        // Reports & Analytics
        Route::view('/reports', 'admin.reports')
            ->name('reports.index');

        // Audit Logs
        Route::view('/audit-logs', 'admin.audit-logs')
            ->name('audit-logs.index');
    });

/*
|--------------------------------------------------------------------------
| Identity Verification Review (admin and staff)
|--------------------------------------------------------------------------
*/

foreach (['admin', 'staff'] as $role) {
    Route::middleware(['auth', 'role:' . $role])
        ->prefix($role . '/identity-verification')
        ->name($role . '.identity-verification.')
        ->controller(IdentityReviewController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{verification}', 'show')->name('show');
            Route::get('/{verification}/document', 'document')->name('document');
            Route::post('/{verification}/approve', 'approve')->name('approve');
            Route::post('/{verification}/reject', 'reject')->name('reject');
        });
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');