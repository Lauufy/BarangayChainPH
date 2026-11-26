<?php

use App\Http\Controllers\Citizen\DashboardController as CitizenDashboardController;
use App\Http\Controllers\Citizen\IdentityVerificationController;
use App\Http\Controllers\Citizen\NotificationController;
use App\Http\Controllers\Citizen\PrivacyRequestController;
use App\Http\Controllers\Citizen\ProfileController;
use App\Http\Controllers\Citizen\ServiceApplicationController;
use App\Http\Controllers\Citizen\ServiceRequestController;
use App\Http\Controllers\Citizen\TicketController;
use App\Http\Controllers\Shared\PermitResubmissionController;
use App\Http\Controllers\Citizen\HelpArticleController;
use Illuminate\Support\Facades\Route;

// ===== Citizen routes (logged-in citizens only) =====

Route::middleware(['auth', 'nocache', 'role:citizen'])
    ->prefix('citizen')
    ->name('citizen.')
    ->group(function () {

        // Citizen home dashboard.
        Route::get('/dashboard', [CitizenDashboardController::class, 'index'])->name('dashboard');

        // Services list page (it loads its own data).
        Route::view('/services', 'citizen.services.index')->name('services');

        Route::get('/applications/{serviceRequest}/resubmit', [PermitResubmissionController::class, 'edit'])->name('resubmissions.edit');
        Route::post('/applications/{serviceRequest}/resubmit', [PermitResubmissionController::class, 'submit'])->name('resubmissions.submit');

        // Documents and applications pages.
        // Each method must authorize ownership of the bound serviceRequest.
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

        Route::controller(TicketController::class)->prefix('help-center')->name('help.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->middleware('throttle:5,1,help-request:')->name('store');
            Route::get('/tickets/create', 'create')->name('create');
            Route::get('/tickets', 'tickets')->name('tickets');
            Route::get('/{ticket}', 'showHelp')->name('show');
            Route::post('/{ticket}', 'replyHelp')->middleware('throttle:20,1')->name('reply');
        });
        Route::get('/help/categories/{category:slug}', [HelpArticleController::class, 'category'])
            ->name('help.category');
        Route::get('/help/articles/{article:slug}', [HelpArticleController::class, 'show'])
            ->name('help.article');

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

        // Ask to see, correct, or delete personal data, limited to 5 per minute.
        Route::post('/privacy-request', [PrivacyRequestController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('privacy-request');

        // Identity verification steps.
        Route::controller(IdentityVerificationController::class)->group(function () {
            // Show the identity verification page.
            Route::get('/identity-verification', 'show')->name('identity-verification');
            // Keep the personal details step in the session.
            Route::post('/identity-verification/details', 'storeDetails')->name('identity-verification.details');

            // Keep ID and selfie uploads in private staging until final submission.
            Route::post('/identity-verification/id', 'storeId')
                ->middleware('throttle:10,1')
                ->name('identity-verification.id');

            Route::post('/identity-verification/selfie', 'storeSelfie')
                ->middleware('throttle:10,1')
                ->name('identity-verification.selfie');

            // Persist the session draft after consent.
            Route::post('/identity-verification/document', 'storeDocument')
                ->middleware('throttle:10,1')
                ->name('identity-verification.document');

            // Serve a staged file through a short-lived URL for this session.
            Route::get('/identity-verification/staged/{type}', 'stagedFile')
                ->name('identity-verification.staged-file');

            // Serve an uploaded verification file (must belong to the logged-in citizen).
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

            // Slugs that would collide with the fixed citizen routes above.
            $reservedSlugs = [
                'help-center',
                'dashboard',
                'services',
                'documents',
                'applications',
                'profile',
                'identity-verification',
                'notifications',
            ];

            // Service application wizard (one route pair per service in the config).
            foreach (array_keys(config('barangay_services.services')) as $slug) {
                throw_if(
                    in_array($slug, $reservedSlugs, true),
                    LogicException::class,
                    "Service slug [{$slug}] conflicts with a reserved citizen route."
                );

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
