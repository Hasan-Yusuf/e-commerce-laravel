<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\DTOs\AppliedCoupon;
use App\DTOs\CartSummary;
use App\DTOs\CheckoutState;
use App\DTOs\CheckoutSummary;
use App\DTOs\CouponValidationResult;
use App\DTOs\CustomerData;
use App\DTOs\OrderPlacementResult;
use App\DTOs\ShippingAddressData;
use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use App\Services\Order\OrderService;

/**
 * The rules of the checkout flow: where the shopper is, what an answer does to
 * the flow, and the data each step renders.
 *
 * The state itself is a CheckoutState and where it lives is CheckoutStateStore's
 * business; what the account already knows is CheckoutPrefill's; writing the
 * order itself is OrderService's. This class only decides what happens when a
 * shopper answers a step, asks for one, or places the order.
 */
class CheckoutService
{
    public function __construct(
        private CheckoutStateStore $store,
        private CheckoutPrefill $prefill,
        private CartService $cartService,
        private CouponService $couponService,
        private OrderService $ordersService,
    ) {}

    /**
     * Begin a checkout: the state the shopper left behind — or a blank flow —
     * with everything the account already knows filled in.
     *
     * This is what makes the checkout survive a reload, a step that failed
     * validation, and the login round-trip a guest makes before placing the
     * order: none of them start the flow over.
     */
    public function startCheckout(): void
    {
        $this->store->put($this->prefill->apply($this->store->get()));
    }

    public function state(): CheckoutState
    {
        return $this->store->get();
    }

    public function isStateComplete(): bool
    {
        $state = $this->state();

        return $state->plan->isComplete($state->progress);
    }

    /**
     * Show a step. A step the shopper has answered can be revisited and
     * corrected, but the flow can never be skipped ahead of: anything past the
     * next unanswered step lands on that step instead.
     */
    public function showStep(CheckoutStep $step): void
    {
        $state = $this->state();
        $reachable = $state->plan->reachableStep($state->progress);

        $canShow = $state->plan->hasAnswered($step, $state->progress) || $step === $reachable;

        $this->store->put(
            $state->withStep($canShow ? $step : $reachable),
        );
    }

    /**
     * Record the contact details for the customer step.
     */
    public function saveCustomer(CustomerData $customer): void
    {
        $this->save(
            CheckoutStep::CUSTOMER,
            $this->state()->withCustomer($customer),
        );
    }

    /**
     * Record the address for the shipping step.
     */
    public function saveShippingAddress(ShippingAddressData $address): void
    {
        $this->save(
            CheckoutStep::SHIPPING_ADDRESS,
            $this->state()->withShippingAddress($address),
        );
    }

    /**
     * Record the method chosen on the delivery step.
     */
    public function saveDeliveryMethod(DeliveryMethod $method): void
    {
        $this->save(
            CheckoutStep::DELIVERY,
            $this->state()->withDeliveryMethod($method),
        );
    }

    /**
     * Record the method chosen on the payment step.
     */
    public function savePaymentMethod(PaymentMethod $method): void
    {
        $this->save(
            CheckoutStep::PAYMENT,
            $this->state()->withPaymentMethod($method),
        );
    }

    public function getStep(): CheckoutStep
    {
        return $this->state()->step;
    }

    /**
     * Apply the coupon a typed code names to the checkout: a code that applies
     * is remembered on the state (so it survives a reload and a step
     * correction), and one that does not is reported back with its reason.
     */
    public function applyCoupon(string $code): CouponValidationResult
    {
        $coupon = $this->couponService->resolve($code);

        if ($coupon === null) {
            return CouponValidationResult::invalid('checkout.coupon.invalid');
        }

        $result = $this->couponService->validate(
            $coupon,
            $this->cartService->subtotalInFils(),
        );

        if (! $result->isValid) {
            return $result;
        }

        $this->store->put(
            $this->state()->withCoupon(
                new AppliedCoupon(
                    id: (int) $coupon->getKey(),
                    code: $coupon->code,
                    discountInFils: $result->discountInFils,
                ),
            ),
        );

        return $result;
    }

    /**
     * Forget the applied coupon, returning the checkout to full price.
     */
    public function removeCoupon(): void
    {
        $this->store->put($this->state()->withCoupon(null));
    }

    public function getCurrentStepUrl(): string
    {
        return $this->getStep()->url();
    }

    /**
     * The number a step prints in this checkout's flow, or null when the
     * checkout does not ask for it.
     */
    public function numberOf(CheckoutStep $step): ?int
    {
        return $this->state()->plan->number($step);
    }

    /**
     * The step a Back button on $step returns to, or null at the start of the
     * flow. It is the state's answer rather than the enum's: collecting the
     * order from the shop takes the address step out of the flow, so the payment
     * step backs onto the delivery one instead.
     */
    public function previousStep(CheckoutStep $step): ?CheckoutStep
    {
        return $this->state()->plan->before($step);
    }

    /**
     * The flow's steps with the state each is in from where the shopper stands:
     * the one being shown, the ones already answered, the ones still ahead, and
     * the number each prints. A step this checkout does not require is not one
     * of them.
     *
     * @return array<int, array{step: CheckoutStep, state: string, number: int|null}>
     */
    public function steps(): array
    {
        $state = $this->state();

        return array_map(
            static fn (CheckoutStep $step): array => [
                'step' => $step,
                'state' => match (true) {
                    $step === $state->step => 'current',
                    $state->plan->hasAnswered($step, $state->progress) => 'complete',
                    default => 'pending',
                },
                'number' => $state->plan->number($step),
            ],
            $state->plan->visible,
        );
    }

    /**
     * The checkout as the shopper answered it, plus the cart it would be placed
     * from — everything the review step renders.
     */
    public function summary(): CheckoutSummary
    {
        return CheckoutSummary::fromCheckout(
            $this->state(),
            $this->cartSummary(),
        );
    }

    /**
     * Whether there is anything to check out.
     */
    public function hasCartItems(): bool
    {
        return ! $this->cartService->isEmpty();
    }

    public function attemptPlaceOrder(
        User $user,
        ?string $note = null,
    ): OrderPlacementResult {
        $result = $this->ordersService->placeOrder(
            $this->state(),
            $this->cartSummary(),
            $user,
            $note,
        );

        if (! $result->wasPlaced()) {
            $this->cartService->revalidate();

            return $result;
        }

        $this->cartService->clear();
        $this->reset();

        return $result;
    }

    /**
     * Forget the checkout, once the order has been placed.
     */
    public function reset(): void
    {
        $this->store->forget();
    }

    /**
     * The cart's summary with the applied coupon folded into its totals — the
     * single shape both the review and the placement read their money from.
     */
    private function cartSummary(): CartSummary
    {
        return $this->couponService->applyToCart(
            $this->cartService->cartSummary(),
            $this->state()->coupon,
        );
    }

    /**
     * Take an answered step and move the flow along: answering a step for the
     * first time advances to the next one, while correcting a step that was
     * already answered returns to the review, which is where it was changed
     * from.
     */
    private function save(CheckoutStep $step, CheckoutState $state): void
    {
        $orderStep = $state->plan->order($step);
        $alreadyAnswered = $state->plan->hasAnswered($step, $state->progress);

        // A step answered for the first time advances to the next one; correcting
        // a step that was already answered returns to the review, which is where
        // it was changed from. A correction that leaves the flow incomplete —
        // turning a collection back into a delivery, which puts the address back
        // in — lands on the step that is now next instead.
        $target = $alreadyAnswered ? CheckoutStep::REVIEW : $state->plan->after($step);

        $updated = $state->withProgress(max($state->progress, $orderStep ?? 0));

        $next = $target === CheckoutStep::REVIEW && ! $updated->plan->isComplete($updated->progress)
            ? $updated->plan->reachableStep($updated->progress)
            : $target;

        $this->store->put($updated->withStep($next));
    }
}
