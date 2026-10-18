<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Browsing
|--------------------------------------------------------------------------
|
| The public storefront: no authentication anywhere, so a guest can look
| around, search and filter without being asked to sign in.
|
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/products', [ProductController::class, 'index'])->name(
    'products.index',
);

Route::get('/products/{product:slug}', [
    ProductController::class,
    'show',
])->name('products.show');

Route::get('/category', [CategoryController::class, 'index'])->name(
    'category.index',
);

Route::get('/category/{category:slug}', [
    CategoryController::class,
    'show',
])->name('category.show');
