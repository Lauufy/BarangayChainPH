<?php

use App\Http\Controllers\PublicContactController;
use App\Http\Controllers\Shared\PermitVerificationController;
use App\Http\Controllers\Shared\SyncVersionController;
use App\Http\Controllers\Shared\TicketParticipantPhotoController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ===== Public routes (no login required) =====

// Landing page.
Route::get('/', function () {
    $user = Auth::user();
    $dashboard = match (true) {
        $user instanceof User && $user->isCitizen() => route('citizen.dashboard'),
        $user instanceof User && ($user->isAdmin() || $user->isStaff()) => route('admin.dashboard'),
        default => null,
    };

    return view('home', compact('dashboard'));
})->name('home');

Route::post('/contact', [PublicContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

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

// Live-sync version check polled by the admin and citizen layouts (logged-in users only).
// SyncVersionController must reject a scope that does not match the user's role.
Route::get('/sync/version/{scope}', SyncVersionController::class)
    ->whereIn('scope', ['admin', 'citizen'])
    ->middleware(['auth', 'nocache', 'throttle:120,1'])
    ->name('sync.version');
