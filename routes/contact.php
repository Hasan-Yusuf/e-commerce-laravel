<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Contact
|--------------------------------------------------------------------------
|
| The way to write to the store. Anyone can use it — a shopper does not need
| an account to ask about an order — so only the write is throttled, and only
| so that one client cannot fill the inbox on its own.
|
*/

Route::get('/contact', [ContactController::class, 'index'])->name(
    'contact.index',
);

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact.store');
