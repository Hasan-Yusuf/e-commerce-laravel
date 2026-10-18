<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\CartSummary;
use App\DTOs\CheckoutState;
use App\DTOs\OrderProcessingContext;
use App\Enums\DeliveryMethod;
use App\Services\Account\AddressService;
use Closure;
use LogicException;

/**
 * The gate the placement passes before an order is written: the checkout must be
 * answered and the cart must hold something, and the address the order ships to
 * is resolved here. That address is the one part of the order the checkout keeps
 * as an answer rather than as a record, so resolving it — and refusing anything
 * incomplete — is what lets CreateOrder be only the write itself.
 *
 * The coupon is not re-checked here: the cart the pipeline is handed already
 * carries the coupon folded in as it stands now (see CouponService::applyToCart),
 * and one that stopped applying arrives as no coupon at all.
 */
class VerifyOrderData
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $state = $context->checkoutOrFail();
        $cart = $context->cartOrFail();
        $user = $context->userOrFail();

        $this->assertPlaceable($state, $cart);

        // Collecting the order in the shop ships nothing, so no address is
        // written: the order keeps a null one rather than an empty one.
        if (! $state->deliveryMethod->is(DeliveryMethod::PICKUP)) {
            $context = $context->withAddress(
                $this->addressService->saveShippingAddress(
                    $user,
                    $state->shippingAddress,
                ),
            );
        }

        return $next($context->recordPipe(self::class));
    }

    /**
     * The flow turns an unanswered checkout or an empty cart away before it ever
     * places (see CheckoutFlow::place), so reaching the pipeline with either is a
     * programming error rather than the shopper's doing — and one that would
     * write an order that should not exist.
     */
    private function assertPlaceable(CheckoutState $state, CartSummary $cart): void
    {
        if (! $state->plan->isComplete($state->progress)) {
            throw new LogicException(
                'An unanswered checkout reached the placement pipeline.',
            );
        }

        if ($cart->lines->isEmpty()) {
            throw new LogicException(
                'An empty cart reached the placement pipeline.',
            );
        }
    }
}
