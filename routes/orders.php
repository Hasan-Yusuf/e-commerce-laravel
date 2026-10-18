<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Orders
|--------------------------------------------------------------------------
|
| The order history is the account's own: a shopper sees the orders they
| placed and nobody else's, which is what the OrderPolicy enforces. The
| confirmation sits with the checkout instead, because the token it carries
| is what a guest has in place of an account.
|
*/

Route::middleware('auth')->group(function (): void {
    Route::get('/orders', [OrderController::class, 'index'])->name(
        'orders.index',
    );

    Route::get('/orders/{order:number}', [OrderController::class, 'show'])->name(
        'orders.show',
    );
});
