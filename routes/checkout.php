<?php

use App\Enums\CheckoutStep;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Checkout
 |--------------------------------------------------------------------------
 |
 | A guest can answer every step of the checkout, so only placing the order
 | sits behind `auth`: that endpoint returns the shopper to the review step it
 | was submitted from, once they have an account to place it with. What it
 | places lands on a confirmation whose URL carries the order's access token.
 |
 | Each step has its own URL — /checkout/customer, /checkout/delivery, … —
 | named after the step, and a step the shopper has not reached yet lands them
 | on the one they should be on instead.
 |
 */

Route::get('/checkout', [CheckoutController::class, 'index'])->name(
    'checkout.index',
);

Route::get('/checkout/{step}', [CheckoutController::class, 'show'])
    ->name('checkout.step')
    ->whereIn('step', CheckoutStep::cases());

Route::post('/checkout/customer', [
    CheckoutController::class,
    'storeCustomer',
])->name('checkout.customer.store');

Route::post('/checkout/shipping_address', [
    CheckoutController::class,
    'storeShippingAddress',
])->name('checkout.shipping.store');

Route::post('/checkout/delivery', [
    CheckoutController::class,
    'storeDelivery',
])->name('checkout.delivery.store');

Route::post('/checkout/payment', [
    CheckoutController::class,
    'storePayment',
])->name('checkout.payment.store');

// Applying a coupon is part of the guest-answerable review, so it carries no
// `auth` either; the shopper is returned to the review with the code applied or
// with the reason it was refused.
Route::post('/checkout/coupon', [
    CheckoutController::class,
    'storeCoupon',
])->name('checkout.coupon.store');

Route::post('/checkout/coupon/remove', [
    CheckoutController::class,
    'removeCoupon',
])->name('checkout.coupon.remove');

Route::post('/checkout/review', [CheckoutController::class, 'place'])
    ->middleware('auth')
    ->name('checkout.place');

// Where placing the order leaves the shopper: the confirmation carries the
// order's access token, so it can be opened by whoever placed the order —
// signed in or not.
Route::get('/checkout/confirmation/{order:number}', [
    OrderController::class,
    'confirmation',
])->name('checkout.confirmation');
