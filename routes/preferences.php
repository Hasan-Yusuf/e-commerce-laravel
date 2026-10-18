<?php

use App\Http\Controllers\LangController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | Preferences & utilities
 |--------------------------------------------------------------------------
 */

Route::post('/lang', LangController::class)->name('lang');
