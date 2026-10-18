<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;

/**
 * Cash on delivery: no provider is involved, so the driver only writes down
 * what the courier is expected to collect. The payment stays pending until the
 * money is handed over.
 */
class CodGateway implements PaymentGateway
{
    #[\Override]
    public function method(): PaymentMethod
    {
        return PaymentMethod::COD;
    }

    #[\Override]
    public function createPaymentFor(Order $order): Payment
    {
        return $order
            ->payments()
            ->create([
                'method' => PaymentMethod::COD,
                'status' => PaymentStatus::PENDING,
                'amount_in_fils' => $order->total_amount_in_fils,
            ]);
    }
}
