<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\DTOs\CartSummary;
use App\DTOs\CheckoutState;
use App\DTOs\OrderPlacementResult;
use App\DTOs\OrderProcessingContext;
use App\Models\User;
use App\Pipelines\Orders\Pipes\CreateOrder;
use App\Pipelines\Orders\Pipes\LockProducts;
use App\Pipelines\Orders\Pipes\NotifyLowStock;
use App\Pipelines\Orders\Pipes\RecalculateReservedStock;
use App\Pipelines\Orders\Pipes\RecordCouponUsage;
use App\Pipelines\Orders\Pipes\RecordPayment;
use App\Pipelines\Orders\Pipes\VerifyOrderData;
use App\Pipelines\Orders\Pipes\VerifyStock;

final class OrderPlacer extends OrderPipeline
{
    protected const array PIPES = [
        LockProducts::class,
        VerifyStock::class,
        VerifyOrderData::class,
        CreateOrder::class,
        RecordCouponUsage::class,
        RecalculateReservedStock::class,
        RecordPayment::class,
        NotifyLowStock::class,
    ];

    /**
     * The result says which way it went: the order that now exists, or the
     * fact that the stock moved under the shopper and nothing was written.
     */
    public function __invoke(
        CheckoutState $state,
        CartSummary $cart,
        User $user,
        ?string $note = null,
    ): OrderPlacementResult {
        $context = $this->run(
            new OrderProcessingContext(
                checkout: $state,
                cart: $cart,
                user: $user,
                note: $note,
            ),
        );

        return $context->result ?? OrderPlacementResult::stockChanged();
    }
}
