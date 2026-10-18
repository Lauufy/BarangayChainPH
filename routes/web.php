<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IdentityReviewController;
use App\Http\Controllers\Admin\PermitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Citizen\IdentityVerificationController;
use App\Http\Controllers\Citizen\ProfileController;
use App\Http\Controllers\Citizen\ServiceRequestController;
use App\Http\Controllers\PermitVerificationController;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
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

Route::get('/verify/permit/{serviceRequest}', [PermitVerificationController::class, 'show'])
    ->middleware('signed')
    ->name('permits.verify');

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

    //Google Auth
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');

    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
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

        Route::get('/services', function (Request $request) {
            $pendingServices = ServiceRequest::whereBelongsTo($request->user())
                ->where('status', ServiceRequest::PENDING)
                ->pluck('service_type')
                ->all();

            return view('citizen.services', compact('pendingServices'));
        })->name('services');

        // Documents & Applications
        Route::get('/documents', [ServiceRequestController::class, 'documents'])
            ->name('documents');
        Route::get('/applications', [ServiceRequestController::class, 'applications'])
            ->name('applications');
        Route::get('/applications/{serviceRequest}', [ServiceRequestController::class, 'showApplication'])
            ->name('applications.show');
        Route::get('/applications/{serviceRequest}/print', [ServiceRequestController::class, 'printApplication'])
            ->name('applications.print');
        Route::post('/applications/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancelApplication'])
            ->name('applications.cancel');

        // Barangay Clearance
        Route::get('/barangay-clearance', [ServiceRequestController::class, 'clearance'])
            ->name('barangay-clearance');
        Route::post('/barangay-clearance/preview', [ServiceRequestController::class, 'previewClearance'])
            ->name('barangay-clearance.preview');
        Route::post('/barangay-clearance/modify', [ServiceRequestController::class, 'modifyClearance'])
            ->name('barangay-clearance.modify');
        Route::post('/barangay-clearance/proceed', [ServiceRequestController::class, 'proceedClearance'])
            ->name('barangay-clearance.proceed');

        // Certificate of Residency
        Route::get('/certificate-of-residency', [ServiceRequestController::class, 'residency'])
            ->name('certificate-of-residency');
        Route::post('/certificate-of-residency/preview', [ServiceRequestController::class, 'previewResidency'])
            ->name('certificate-of-residency.preview');
        Route::post('/certificate-of-residency/modify', [ServiceRequestController::class, 'modifyResidency'])
            ->name('certificate-of-residency.modify');
        Route::post('/certificate-of-residency/proceed', [ServiceRequestController::class, 'proceedResidency'])
            ->name('certificate-of-residency.proceed');

        // Certificate of Indigency
        Route::get('/certificate-of-indigency', [ServiceRequestController::class, 'indigency'])
            ->name('certificate-of-indigency');
        Route::post('/certificate-of-indigency/preview', [ServiceRequestController::class, 'previewIndigency'])
            ->name('certificate-of-indigency.preview');
        Route::post('/certificate-of-indigency/modify', [ServiceRequestController::class, 'modifyIndigency'])
            ->name('certificate-of-indigency.modify');
        Route::post('/certificate-of-indigency/proceed', [ServiceRequestController::class, 'proceedIndigency'])
            ->name('certificate-of-indigency.proceed');

        // Profile
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

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Notification dismissed.']);
            }

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
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // User & Role Management
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');

        Route::post('/users', [UserController::class, 'store'])
            ->name('users.store');

        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');

        // Permit & Clearance Processing
        Route::get('/permits', [PermitController::class, 'index'])
            ->name('permits.index');
        Route::get('/permits/{serviceRequest}', [PermitController::class, 'show'])
            ->name('permits.show');
        Route::post('/permits/{serviceRequest}/approve', [PermitController::class, 'approve'])
            ->name('permits.approve');
        Route::post('/permits/{serviceRequest}/reject', [PermitController::class, 'reject'])
            ->name('permits.reject');
        Route::patch('/permits/{serviceRequest}/print-limit', [PermitController::class, 'updatePrintLimit'])
            ->name('permits.print-limit.update');

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
