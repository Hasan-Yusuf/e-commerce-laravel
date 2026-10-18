<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| The form pages are for guests only — a shopper who is already signed in is
| sent home from them. Signing out is the one action that needs a session.
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');

    // Throttled so a run of wrong passwords cannot be ground through; the
    // limiter keys on the client, so it also cannot be used to probe accounts.
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name(
        'register',
    );

    Route::post('/register', [RegisterController::class, 'store'])->name(
        'register.store',
    );

    // Placeholder page; the reset flow itself comes later.
    Route::get('/forgot-password', ForgotPasswordController::class)->name(
        'password.request',
    );
});

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');
