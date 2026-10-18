<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Settles the money an order is owed: the cash-on-delivery payment an order is
 * placed with stays pending until the courier hands the cash over, which is
 * what an administrator records here. The payment and the order's own
 * `paid_at` stamp move together so the two can never disagree.
 */
class PaymentService
{
    /**
     * Mark the order's newest pending payment as collected and stamp the order.
     *
     * @throws DomainException when the order has no pending payment to collect
     */
    public function settle(Order $order): Payment
    {
        return DB::transaction(static function () use ($order): Payment {
            /** @var Payment|null $payment */
            $payment = $order
                ->payments()
                ->where('status', PaymentStatus::PENDING)
                ->latest('id')
                ->first();

            if ($payment === null) {
                throw new DomainException(
                    'The order has no pending payment to collect.',
                );
            }

            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]);

            $order->update(['paid_at' => now()]);

            return $payment;
        });
    }
}
