<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\CartSummary;
use App\DTOs\OrderPlacementResult;
use App\DTOs\OrderProcessingContext;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderCalculator;
use Closure;
use Illuminate\Support\Str;

/**
 * Write the order and its lines, and nothing else. What the order is written
 * from is settled before this pipe — the checkout is checked and the address
 * resolved by VerifyOrderData — and the coupon's use is counted after it, by
 * RecordCouponUsage.
 *
 * Each line is a snapshot — the name, SKU and price it was bought at are kept
 * on the order, so a product renamed, re-priced or removed afterwards still
 * reads back as what was bought. Nothing here holds the stock those lines take:
 * the hold is derived from the lines later in the chain.
 */
class CreateOrder
{
    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $state = $context->checkoutOrFail();
        $cart = $context->cartOrFail();
        $user = $context->userOrFail();
        $address = $context->address;

        /** @var Order $order */
        $order = $user->orders()->create([
            'status' => OrderStatus::getDefaultStatus(),
            'delivery_method' => $state->deliveryMethod,
            ...OrderCalculator::forCart($cart, $cart->coupon)->toArray(),
            'shipping_address_id' => $address?->getKey(),
            'billing_address_id' => $address?->getKey(),
            'coupon_id' => $cart->coupon?->id,
            'coupon_code' => $cart->coupon?->code,
            'notes' => $context->note,

            'checkout_token' => $state->token,
            'access_token' => (string) Str::uuid(),
        ]);

        $this->recordItems($order, $cart);

        return $next(
            $context
                ->withOrder($order)
                ->withResult(OrderPlacementResult::placed($order))
                ->recordPipe(self::class),
        );
    }

    private function recordItems(Order $order, CartSummary $cart): void
    {
        foreach ($cart->lines as $line) {
            $product = $line->product;

            if ($product === null) {
                continue;
            }

            $order
                ->orderItems()
                ->create([
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $line->quantity,
                    'unit_price_in_fils' => $line->currentPriceInFils,
                ]);
        }
    }
}
