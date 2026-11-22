<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BlockchainLedgerController;
use App\Http\Controllers\Admin\ContactMessageController;
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
use App\Http\Controllers\Auth\LoginController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

            // Change a user's account status.
            Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
                ->name('users.status');

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
        // ExportController must check the permission for each module and audit-log every export.
        Route::get('/exports/{module}', [ExportController::class, 'show'])
            ->where('module', '[a-z\-]+')
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

                // Serve a document attached to a permit request (document must belong to it).
                Route::get('/permits/{serviceRequest}/documents/{document}', 'document')
                    ->scopeBindings()
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

        Route::middleware('permission:tickets.reply')
            ->prefix('contact-messages')
            ->name('contact-messages.')
            ->controller(ContactMessageController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{contactMessage}', 'show')->name('show');
                Route::post('/{contactMessage}/reply', 'reply')
                    ->middleware('throttle:30,1')
                    ->name('reply');
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
            ->middleware('permission:contracts.view')
            ->name('smart-contracts.index');

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
