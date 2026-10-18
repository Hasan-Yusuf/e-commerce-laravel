<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cart & wishlist
|--------------------------------------------------------------------------
|
| Both lists resolve to the session for a guest and to the account for a
| signed-in shopper, so neither needs authentication. The wishlist has no
| page of its own: it renders under the cart (cart.index#wishlist), which is
| also where the two lists move entries into each other.
|
*/

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

Route::post('/cart', [CartController::class, 'add'])->name('cart.add');

// Both verbs reach the same handler: the quantity control sends PATCH, while
// a plain form fallback can send PUT.
Route::addRoute(['PATCH', 'PUT'], '/cart/items/{item}', [
    CartController::class,
    'update',
])->name('cart.update');

Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name(
    'cart.remove',
);

Route::post('/cart/items/{item}/move', [
    CartController::class,
    'moveToWishlist',
])->name('cart.moveToWishlist');

Route::post('/cart/move-all', [
    CartController::class,
    'moveAllToWishlist',
])->name('cart.moveAllToWishlist');

Route::post('/wishlist', [WishlistController::class, 'add'])->name(
    'wishlist.add',
);

Route::delete('/wishlist/{item}', [WishlistController::class, 'remove'])->name(
    'wishlist.remove',
);

Route::post('/wishlist/{item}/move', [
    WishlistController::class,
    'moveToCart',
])->name('wishlist.moveToCart');

Route::post('/wishlist/move-all', [
    WishlistController::class,
    'moveAllToCart',
])->name('wishlist.moveAllToCart');
