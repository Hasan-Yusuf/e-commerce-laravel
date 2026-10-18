<?php

use App\Services\Payments\CodGateway;

return [
    /*
    |--------------------------------------------------------------------------
    | Payment gateways
    |--------------------------------------------------------------------------
    |
    | The payment drivers the storefront offers, in the order they are listed.
    | Each driver implements App\Services\Payments\PaymentGateway and reports
    | the method it handles. Cash on delivery is the only method in v1: adding
    | a card or wallet method means writing a driver, implementing the same
    | interface, and appending it here — the checkout flow does not change.
    |
    */

    'gateways' => [
        CodGateway::class,
    ],
];
