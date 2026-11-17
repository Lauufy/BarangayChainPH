<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BlockchainLedgerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentAuthenticationController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\IdentityReviewController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PermitController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SmartContractController;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ===== Public routes (no login required) =====

// Landing page.
Route::view('/', 'home')->name('home');
// Terms of service page.
Route::view('/terms', 'legal.terms')->name('terms');
// Privacy policy page.
Route::view('/privacy', 'legal.privacy')->name('privacy');

// Serves a ticket participant's photo to authenticated citizens, admins, and staff only.
Route::get('/tickets/{ticket}/participants/{participant}/photo', [
    TicketParticipantPhotoController::class,
    'show',
])->middleware(['auth', 'nocache', 'role:citizen,admin,staff'])
    ->name('tickets.participants.photo');

// Public permit verification page, opened from the QR code on issued documents.
Route::get('/verify/permit/{documentUuid}', [
    PermitVerificationController::class,
    'show',
])->whereUuid('documentUuid')
    ->middleware('throttle:60,1')
    ->name('permits.verify');

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

    // Handle the response coming back from Google.
    Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
});

// ===== Sensitive auth pages (never cached by the browser) =====

Route::middleware('nocache')->group(function () {
    // ===== Google sign-in password setup =====

    // Show the password setup form for users who signed in with Google.
    Route::get('/auth/google/password', [GoogleController::class, 'showPasswordSetup'])
        ->name('google.password.setup');
    // Save the password for a Google user, limited to 5 per minute.
    Route::put('/auth/google/password', [GoogleController::class, 'storePasswordSetup'])
        ->middleware('throttle:5,1')
        ->name('google.password.setup.store');

    // ===== Email verification and login two-factor (OTP) =====

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

// ===== Citizen routes (logged-in citizens only) =====

Route::middleware(['auth', 'nocache', 'role:citizen'])
    ->prefix('citizen')
    ->name('citizen.')
    ->group(function () {

        // Citizen home dashboard.
        Route::get('/dashboard', [CitizenDashboardController::class, 'index'])->name('dashboard');

        // Services list page (it loads its own data).
        Route::view('/services', 'citizen.services.index')->name('services');

        // Documents and applications pages.
        Route::controller(ServiceRequestController::class)->group(function () {
            // List the citizen's issued documents.
            Route::get('/documents', 'documents')->name('documents');

            // List the citizen's applications.
            Route::get('/applications', 'applications')->name('applications');
            // Show one application.
            Route::get('/applications/{serviceRequest}', 'showApplication')->name('applications.show');
            // Printable view of one application.
            Route::get('/applications/{serviceRequest}/print', 'printApplication')->name('applications.print');
            // Cancel one application.
            Route::post('/applications/{serviceRequest}/cancel', 'cancelApplication')->name('applications.cancel');
        });

        // Show the support ticket of an application.
        Route::get('/applications/{serviceRequest}/ticket', [TicketController::class, 'show'])
            ->name('tickets.show');

        // Post a reply on the ticket, limited to 20 per minute.
        Route::post('/applications/{serviceRequest}/ticket', [TicketController::class, 'reply'])
            ->middleware('throttle:20,1')
            ->name('tickets.reply');

        // Show the citizen profile.
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        // Serve the profile photo file.
        Route::get('/profile/photo', [ProfileController::class, 'showPhoto'])->name('profile.photo.show');

        // Update the two-factor setting.
        Route::put('/profile/two-factor', [ProfileController::class, 'updateTwoFactor'])
            ->name('profile.two-factor');

        // Identity verification steps.
        Route::controller(IdentityVerificationController::class)->group(function () {
            // Show the identity verification page.
            Route::get('/identity-verification', 'show')->name('identity-verification');
            // Save the personal details step.
            Route::post('/identity-verification/details', 'storeDetails')->name('identity-verification.details');

            // Upload the ID document, limited to 10 per minute.
            Route::post('/identity-verification/document', 'storeDocument')
                ->middleware('throttle:10,1')
                ->name('identity-verification.document');

            // Serve an uploaded verification file.
            Route::get('/identity-verification/document/{verification}', 'document')
                ->name('identity-verification.file');
        });

        // Show the notifications list.
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications');

        // Mark all notifications as read.
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
            ->name('notifications.read-all');

        // Mark one notification as read.
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])
            ->name('notifications.read');

        // Routes that need a verified identity.
        Route::middleware('identity.verified')->group(function () {
            // Update the profile details.
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            // Upload a new profile photo.
            Route::post('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');

            // Service application wizard (one route pair per service in the config).
            foreach (array_keys(config('barangay_services.services')) as $slug) {
                // Show the application form for this service.
                Route::get("/{$slug}", [ServiceApplicationController::class, 'create'])
                    ->defaults('slug', $slug)
                    ->name($slug);

                // Submit the application, rate limited by the service-submissions limiter.
                Route::post("/{$slug}", [ServiceApplicationController::class, 'store'])
                    ->defaults('slug', $slug)
                    ->middleware('throttle:service-submissions')
                    ->name("{$slug}.store");
            }
        });
    });

// ===== Admin and staff routes =====

Route::middleware(['auth', 'nocache', 'role:admin,staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Admin and staff home dashboard.
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Show the admin profile.
        Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile');
        // Update the admin profile details.
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        // Serve the admin profile photo file.
        Route::get('/profile/photo', [AdminProfileController::class, 'showPhoto'])->name('profile.photo.show');
        // Upload a new admin profile photo.
        Route::post('/profile/photo', [AdminProfileController::class, 'photo'])->name('profile.photo');
        // Update the two-factor setting.
        Route::put('/profile/two-factor', [AdminProfileController::class, 'updateTwoFactor'])
            ->name('profile.two-factor');

        // Show the notifications list.
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');

        // Mark all notifications as read.
        Route::post('/notifications/read-all', [AdminNotificationController::class, 'readAll'])
            ->name('notifications.read-all');

        // Mark one notification as read.
        Route::post('/notifications/{id}/read', [AdminNotificationController::class, 'read'])
            ->name('notifications.read');

        // User management (admin role only).
        Route::middleware('role:admin')->group(function () {
            // List, create, update, and delete users.
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

            // Show a user's permissions.
            Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])
                ->name('users.permissions');

            // Save a user's permissions.
            Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions'])
                ->name('users.permissions.update');

            // Legacy users export URL, forwards to the shared export route as CSV.
            Route::get('/users/export', fn (Request $request) => redirect()->route(
                'admin.exports.show',
                ['module' => 'users', 'format' => 'csv'] + $request->query()
            ))->name('users.export');
        });

        // Shared export endpoint for all modules, limited to 10 per minute.
        Route::get('/exports/{module}', [ExportController::class, 'show'])
            ->middleware('throttle:10,1')
            ->name('exports.show');

        // Permit management (needs the permits.review permission).
        Route::middleware('permission:permits.review')
            ->controller(PermitController::class)
            ->group(function () {
                // List permit requests.
                Route::get('/permits', 'index')->name('permits.index');
                // Show one permit request.
                Route::get('/permits/{serviceRequest}', 'show')->name('permits.show');
                // Approve a permit request.
                Route::post('/permits/{serviceRequest}/approve', 'approve')->name('permits.approve');
                // Reissue an approved permit.
                Route::post('/permits/{serviceRequest}/reissue', 'reissue')->name('permits.reissue');
                // Reject a permit request.
                Route::post('/permits/{serviceRequest}/reject', 'reject')->name('permits.reject');
                // Retry the failed blockchain record, limited to 10 per minute.
                Route::post('/permits/{serviceRequest}/blockchain-retry', 'retryBlockchain')
                    ->middleware('throttle:10,1')
                    ->name('permits.blockchain-retry');

                // Change the print limit (needs the permits.print-limit permission).
                Route::patch('/permits/{serviceRequest}/print-limit', 'updatePrintLimit')
                    ->middleware('permission:permits.print-limit')
                    ->name('permits.print-limit.update');

                // Serve a document attached to a permit request.
                Route::get('/permits/{serviceRequest}/documents/{document}', 'document')
                    ->name('permits.documents.show');
            });

        // Citizen ticket inbox (needs the tickets.reply permission).
        Route::middleware('permission:tickets.reply')
            ->controller(TicketInboxController::class)
            ->group(function () {
                // List citizen tickets.
                Route::get('/tickets', 'index')->name('tickets.index');
                // Show one ticket.
                Route::get('/tickets/{ticket}', 'show')->name('tickets.show');

                // Reply to a ticket, limited to 30 per minute.
                Route::post('/tickets/{ticket}', 'reply')
                    ->middleware('throttle:30,1')
                    ->name('tickets.reply');
            });

        // Document authentication page (needs the documents.authenticate permission).
        Route::get('/document-authentication', [DocumentAuthenticationController::class, 'index'])
            ->middleware('permission:documents.authenticate')
            ->name('document-authentication.index');

        // Blockchain ledger page (needs the blockchain.view permission).
        Route::get('/blockchain-ledger', [BlockchainLedgerController::class, 'index'])
            ->middleware('permission:blockchain.view')
            ->name('blockchain-ledger.index');

        // Smart contracts page (needs the contracts.view permission).
        Route::get('/smart-contracts', [SmartContractController::class, 'index'])
            ->middleware('permission:contracts.view')->name('smart-contracts.index');

        // Pause or resume the smart contract (admin only, limited to 5 per minute).
        Route::post('/smart-contracts/pause', [SmartContractController::class, 'togglePause'])
            ->middleware(['role:admin', 'throttle:5,1'])
            ->name('smart-contracts.pause');

        // Record a new smart contract deployment (admin only, limited to 10 per minute).
        Route::post('/smart-contracts/deployments', [SmartContractController::class, 'storeDeployment'])
            ->middleware(['role:admin', 'throttle:10,1'])
            ->name('smart-contracts.deployments.store');

        // Reports (needs the reports.view permission).
        Route::middleware('permission:reports.view')
            ->controller(ReportController::class)
            ->group(function () {
                // Show the reports page.
                Route::get('/reports', 'index')->name('reports.index');

                // Report exports (admin only).
                Route::middleware('role:admin')->group(function () {
                    // Legacy CSV export URL, forwards to the shared export route.
                    Route::get('/reports/export.csv', fn (Request $request) => redirect()->route(
                        'admin.exports.show',
                        ['module' => 'reports', 'format' => 'csv'] + $request->query()
                    ))->name('reports.export.csv');
                    // Legacy PDF export URL, forwards to the shared export route.
                    Route::get('/reports/export.pdf', fn (Request $request) => redirect()->route(
                        'admin.exports.show',
                        ['module' => 'reports', 'format' => 'pdf'] + $request->query()
                    ))->name('reports.export.pdf');
                });
            });

        // Audit logs page (needs the audit.view permission).
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit.view')
            ->name('audit-logs.index');
    });

// ===== Identity verification review (admin and staff with identity.review) =====

Route::middleware(['auth', 'nocache', 'role:admin,staff', 'permission:identity.review'])
    ->prefix('admin/identity-verification')
    ->name('admin.identity-verification.')
    ->controller(IdentityReviewController::class)
    ->group(function () {
        // List identity verification submissions.
        Route::get('/', 'index')->name('index');
        // Show one submission.
        Route::get('/{verification}', 'show')->name('show');
        // Serve the submitted ID document.
        Route::get('/{verification}/document', 'document')->name('document');
        // Serve the submitted selfie.
        Route::get('/{verification}/selfie', 'selfie')->name('selfie');
        // Approve the submission.
        Route::post('/{verification}/approve', 'approve')->name('approve');
        // Reject the submission.
        Route::post('/{verification}/reject', 'reject')->name('reject');
    });

// ===== Logout =====

// Log the user out (POST only, logged-in users only).
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');