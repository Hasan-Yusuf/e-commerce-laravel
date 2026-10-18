<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Account
|--------------------------------------------------------------------------
|
| The shopper's own area: an overview of who they are and the way into their
| orders and their wishlist. Everything in it is theirs, so the section needs
| an account — the wishlist it links to does not, and neither does the cart.
|
*/

Route::middleware('auth')->group(function (): void {
    Route::get('/account', [AccountController::class, 'index'])->name(
        'account.index',
    );

    // The profile: the details, the delivery address and the password — each in
    // a form of its own. They all write to the account the session is signed in
    // with, so none of them names one in its URL.
    Route::get('/account/edit', [AccountController::class, 'edit'])->name(
        'account.edit',
    );

    Route::patch('/account', [AccountController::class, 'update'])->name(
        'account.update',
    );

    Route::patch('/account/address', [
        AccountController::class,
        'updateAddress',
    ])->name('account.address.update');

    Route::put('/account/password', [
        AccountController::class,
        'updatePassword',
    ])->name('account.password.update');
});
