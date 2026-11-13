<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BlockchainLedgerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentAuthenticationController;
use App\Http\Controllers\Admin\IdentityReviewController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PermitController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TicketInboxController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Citizen\DashboardController as CitizenDashboardController;
use App\Http\Controllers\Citizen\IdentityVerificationController;
use App\Http\Controllers\Citizen\NotificationController;
use App\Http\Controllers\Citizen\ProfileController;
use App\Http\Controllers\Citizen\ServiceApplicationController;
use App\Http\Controllers\Citizen\ServiceRequestController;
use App\Http\Controllers\Citizen\TicketController;
use App\Http\Controllers\PermitVerificationController;
use App\Http\Controllers\TicketParticipantPhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'home')->name('home');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

Route::get('/tickets/{ticket}/participants/{participant}/photo', [
    TicketParticipantPhotoController::class,
    'show',
])->middleware(['auth', 'role:citizen,admin,staff'])
    ->name('tickets.participants.photo');

// Opened from the QR code on issued documents
Route::get('/verify/permit/{documentUuid}', [
    PermitVerificationController::class,
    'show',
])->whereUuid('documentUuid')
    ->middleware('throttle:60,1')
    ->name('permits.verify');
/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    // Registration
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:10,1,registration')
        ->name('register.store');

    // Login
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    // Password reset (email)
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPassword'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetPassword'])
        ->name('password.reset');

    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    // Password reset (SMS)
    Route::get('/reset-password-sms', [ResetPasswordController::class, 'showSms'])
        ->name('password.sms.show');

    Route::post('/reset-password-sms', [ResetPasswordController::class, 'resetSms'])
        ->middleware('throttle:5,1')
        ->name('password.sms.update');

    // Google authentication
    Route::get('/auth/google', [GoogleController::class, 'redirect'])
        ->name('google.redirect');

    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});

Route::get('/auth/google/password', [GoogleController::class, 'showPasswordSetup'])
    ->name('google.password.setup');
Route::put('/auth/google/password', [GoogleController::class, 'storePasswordSetup'])
    ->middleware('throttle:5,1')
    ->name('google.password.setup.store');

/*
|--------------------------------------------------------------------------
| Email Verification and Login 2FA
|--------------------------------------------------------------------------
*/

Route::get('/email/verify', [OtpController::class, 'show'])
    ->name('verification.otp.show');

Route::post('/email/verify', [OtpController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('verification.otp.verify');

Route::post('/email/verification/resend', [OtpController::class, 'resend'])
    ->middleware('throttle:3,1')
    ->name('verification.otp.resend');

Route::post('/email/verification/cancel', [OtpController::class, 'cancel'])
    ->name('verification.cancel');

// Login 2FA: choose email or SMS
Route::get('/two-factor', [OtpController::class, 'choose'])
    ->name('two-factor.choose');

Route::post('/two-factor', [OtpController::class, 'sendLogin'])
    ->middleware('throttle:3,1')
    ->name('two-factor.send');

/*
|--------------------------------------------------------------------------
| Citizen Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:citizen'])
    ->prefix('citizen')
    ->name('citizen.')
    ->group(function () {

        Route::get('/dashboard', [CitizenDashboardController::class, 'index'])->name('dashboard');

        // The services page loads its own data
        Route::view('/services', 'citizen.services.index')->name('services');

        // Documents and applications
        Route::controller(ServiceRequestController::class)->group(function () {
            Route::get('/documents', 'documents')->name('documents');

            Route::get('/applications', 'applications')->name('applications');
            Route::get('/applications/{serviceRequest}', 'showApplication')->name('applications.show');
            Route::get('/applications/{serviceRequest}/print', 'printApplication')->name('applications.print');
            Route::post('/applications/{serviceRequest}/cancel', 'cancelApplication')->name('applications.cancel');
        });

        // Tickets
        Route::get('/applications/{serviceRequest}/ticket', [TicketController::class, 'show'])
            ->name('tickets.show');

        Route::post('/applications/{serviceRequest}/ticket', [TicketController::class, 'reply'])
            ->middleware('throttle:20,1')
            ->name('tickets.reply');

        // Profile
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('/profile/photo', [ProfileController::class, 'showPhoto'])->name('profile.photo.show');

        Route::put('/profile/two-factor', [ProfileController::class, 'updateTwoFactor'])
            ->name('profile.two-factor');

        // Identity verification
        Route::controller(IdentityVerificationController::class)->group(function () {
            Route::get('/identity-verification', 'show')->name('identity-verification');
            Route::post('/identity-verification/details', 'storeDetails')->name('identity-verification.details');

            Route::post('/identity-verification/document', 'storeDocument')
                ->middleware('throttle:10,1')
                ->name('identity-verification.document');

            Route::get('/identity-verification/document/{verification}', 'document')
                ->name('identity-verification.file');
        });

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications');

        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
            ->name('notifications.read-all');

        Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])
            ->name('notifications.read');

        // Verified citizens only
        Route::middleware('identity.verified')->group(function () {
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');

            // Service application wizard (one route pair per service in the config)
            foreach (array_keys(config('barangay_services.services')) as $slug) {
                Route::get("/{$slug}", [ServiceApplicationController::class, 'create'])
                    ->defaults('slug', $slug)
                    ->name($slug);

                Route::post("/{$slug}", [ServiceApplicationController::class, 'store'])
                    ->defaults('slug', $slug)
                    ->middleware('throttle:service-submissions')
                    ->name("{$slug}.store");
            }
        });
    });

/*
|--------------------------------------------------------------------------
| Admin and Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/photo', [AdminProfileController::class, 'showPhoto'])->name('profile.photo.show');
        Route::post('/profile/photo', [AdminProfileController::class, 'photo'])->name('profile.photo');
        Route::put('/profile/two-factor', [AdminProfileController::class, 'updateTwoFactor'])
            ->name('profile.two-factor');

        // Notifications
        Route::view('/notifications', 'admin.notifications.index')->name('notifications.index');

        Route::post('/notifications/read-all', [AdminNotificationController::class, 'readAll'])
            ->name('notifications.read-all');

        Route::post('/notifications/{id}/read', [AdminNotificationController::class, 'read'])
            ->name('notifications.read');

        // Admin-only user management
        Route::middleware('role:admin')->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])
                ->name('users.permissions');

            Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions'])
                ->name('users.permissions.update');
        });

        // Permit management
        Route::middleware('permission:permits.review')
            ->controller(PermitController::class)
            ->group(function () {
                Route::get('/permits', 'index')->name('permits.index');
                Route::get('/permits/{serviceRequest}', 'show')->name('permits.show');
                Route::post('/permits/{serviceRequest}/approve', 'approve')->name('permits.approve');
                Route::post('/permits/{serviceRequest}/reissue', 'reissue')->name('permits.reissue');
                Route::post('/permits/{serviceRequest}/reject', 'reject')->name('permits.reject');
                Route::post('/permits/{serviceRequest}/blockchain-retry', 'retryBlockchain')
                    ->middleware('throttle:10,1')
                    ->name('permits.blockchain-retry');

                Route::patch('/permits/{serviceRequest}/print-limit', 'updatePrintLimit')
                    ->middleware('permission:permits.print-limit')
                    ->name('permits.print-limit.update');

                Route::get('/permits/{serviceRequest}/documents/{document}', 'document')
                    ->name('permits.documents.show');
            });

        // Citizen ticket inbox
        Route::middleware('permission:tickets.reply')
            ->controller(TicketInboxController::class)
            ->group(function () {
                Route::get('/tickets', 'index')->name('tickets.index');
                Route::get('/tickets/{ticket}', 'show')->name('tickets.show');

                Route::post('/tickets/{ticket}', 'reply')
                    ->middleware('throttle:30,1')
                    ->name('tickets.reply');
            });

        Route::get('/document-authentication', [DocumentAuthenticationController::class, 'index'])
            ->middleware('permission:documents.authenticate')
            ->name('document-authentication.index');

        Route::get('/blockchain-ledger', [BlockchainLedgerController::class, 'index'])
            ->middleware('permission:blockchain.view')
            ->name('blockchain-ledger.index');

        Route::middleware(['role:admin', 'permission:reports.view'])
            ->controller(ReportController::class)
            ->group(function () {
                Route::get('/reports', 'index')->name('reports.index');
                Route::get('/reports/export.csv', 'csv')
                    ->middleware('throttle:10,1')
                    ->name('reports.export.csv');
            });

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit.view')
            ->name('audit-logs.index');
    });

/*
|--------------------------------------------------------------------------
| Identity Verification Review
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,staff', 'permission:identity.review'])
    ->prefix('admin/identity-verification')
    ->name('admin.identity-verification.')
    ->controller(IdentityReviewController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{verification}', 'show')->name('show');
        Route::get('/{verification}/document', 'document')->name('document');
        Route::get('/{verification}/selfie', 'selfie')->name('selfie');
        Route::post('/{verification}/approve', 'approve')->name('approve');
        Route::post('/{verification}/reject', 'reject')->name('reject');
    });

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
