<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| One file per section of the storefront, so a route is found by the area it
| belongs to rather than by reading one long file. Each section carries its
| own notes; this file is the map of them.
|
| The sections are required rather than grouped: this file is itself loaded
| inside the `web` group (see bootstrap/app.php), and a required file
| registers its routes into the same group, so no section repeats the
| middleware or the prefix.
|
*/

require __DIR__.'/browsing.php';
require __DIR__.'/cart-wishlist.php';
require __DIR__.'/checkout.php';
require __DIR__.'/account.php';
require __DIR__.'/orders.php';
require __DIR__.'/auth.php';
require __DIR__.'/contact.php';
require __DIR__.'/preferences.php';
