<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;

/**
 * A payment driver. One driver handles one method: it records the payment an
 * order is settled with and reports what the storefront should call that
 * method. Cash on delivery ships in v1; a card or wallet driver implements
 * this same interface and is registered in config/payments.php, so order
 * creation never has to know which methods exist.
 */
interface PaymentGateway
{
    /**
     * The method this driver handles.
     */
    public function method(): PaymentMethod;

    /**
     * Record the payment the order will be settled with. Money that is not
     * collected at this point stays pending until the driver (or an admin)
     * marks it paid.
     */
    public function createPaymentFor(Order $order): Payment;
}
