<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\EmailVerificationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'store'])
        ->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});


Route::middleware('auth')->group(function () {
    // Show the email verification notice.
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

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




Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

/*
|--------------------------------------------------------------------------
| Citizen Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:citizen'])->group(function () {
    Route::get('/citizen/dashboard', function () {
        return view('citizen.dashboard');
    })->name('citizen.dashboard');
});

/*
|--------------------------------------------------------------------------
| Staff Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', function () {
        return view('staff.dashboard');
    })->name('staff.dashboard');
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');

    Route::post('/admin/users', [UserController::class, 'store'])
        ->name('admin.users.store');

    Route::patch('/admin/users/{user}/status', [UserController::class, 'toggleStatus'])
        ->name('admin.users.toggle-status');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');
});