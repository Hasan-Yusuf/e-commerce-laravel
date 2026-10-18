<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\DTOs\CheckoutSummary;
use App\DTOs\CouponValidationResult;
use App\DTOs\CustomerData;
use App\DTOs\ShippingAddressData;
use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Http\RedirectResponse;

/**
 * The navigation every checkout request shares: the cart guard each step passes
 * through, where a step the shopper has not reached sends them, and how a
 * submission that skipped ahead is answered. It keeps those rules out of the
 * controller, which still reads the request and builds the response.
 */
final class CheckoutFlow
{
    public function __construct(
        private CheckoutService $checkoutService,
        private OrderService $orderService,
    ) {}

    /**
     * Run the checkout action when the shopper has a cart; otherwise send them
     * back to the cart to fill one.
     *
     * @template TResponse
     *
     * @param  callable(): TResponse  $action
     * @return TResponse | RedirectResponse
     */
    public function withCart(callable $action): mixed
    {
        if (! $this->checkoutService->hasCartItems()) {
            return redirect()->route('cart.index');
        }

        return $action();
    }

    /**
     * Begin the flow and return the step to show.
     */
    public function begin(): CheckoutStep
    {
        $this->checkoutService->startCheckout();

        return $this->checkoutService->getStep();
    }

    /**
     * Move the flow to $step when the shopper has reached it, and return the
     * step to show: the asked-for step, or the one the flow has reached.
     */
    public function open(CheckoutStep $step): CheckoutStep
    {
        $this->checkoutService->startCheckout();
        $this->checkoutService->showStep($step);

        return $this->checkoutService->getStep();
    }

    public function saveCustomer(CustomerData $customer): void
    {
        $this->checkoutService->saveCustomer($customer);
    }

    public function saveShippingAddress(ShippingAddressData $address): void
    {
        $this->checkoutService->saveShippingAddress($address);
    }

    public function saveDeliveryMethod(DeliveryMethod $method): void
    {
        $this->checkoutService->saveDeliveryMethod($method);
    }

    public function savePaymentMethod(PaymentMethod $method): void
    {
        $this->checkoutService->savePaymentMethod($method);
    }

    public function applyCoupon(string $code): CouponValidationResult
    {
        return $this->checkoutService->applyCoupon($code);
    }

    public function removeCoupon(): void
    {
        $this->checkoutService->removeCoupon();
    }

    /**
     * The URL of the step the flow has moved to.
     */
    public function currentStepUrl(): string
    {
        return $this->checkoutService->getCurrentStepUrl();
    }

    /**
     * Place the order the flow has gathered, or hand back where the shopper
     * actually is: an order this submission already placed, the step the flow
     * has reached when the submission skipped ahead, the review with the cart
     * brought in line with stock, or the confirmation of the placed order.
     */
    public function place(User $user, #[\SensitiveParameter] string $token, ?string $note): RedirectResponse
    {
        $placed = $this->orderService->getOrderFrom($token, $user);

        if ($placed instanceof Order) {
            return redirect()->to($this->orderService->confirmationUrl($placed));
        }

        return $this->withCart(function () use ($user, $note): RedirectResponse {
            if (! $this->checkoutService->isStateComplete()) {
                return redirect()->to($this->checkoutService->getCurrentStepUrl());
            }

            $result = $this->checkoutService->attemptPlaceOrder($user, $note);

            if ($result->stockChanged) {
                return redirect()
                    ->to(CheckoutStep::getReviewStepUrl())
                    ->with('stock_changed', true)
                    ->withInput();
            }

            return redirect()->to($this->orderService->confirmationUrl($result->orderOrFail()));
        });
    }

    /**
     * @return array<int, array{step: CheckoutStep, state: string}>
     */
    public function steps(): array
    {
        return $this->checkoutService->steps();
    }

    public function summary(): CheckoutSummary
    {
        return $this->checkoutService->summary();
    }

    /**
     * The number a step prints in this checkout's flow, or null when the
     * checkout does not ask for it.
     */
    public function numberOf(CheckoutStep $step): ?int
    {
        return $this->checkoutService->numberOf($step);
    }

    /**
     * The step the given step's Back button returns to, or null at the start of
     * the flow.
     */
    public function previousStep(CheckoutStep $step): ?CheckoutStep
    {
        return $this->checkoutService->previousStep($step);
    }
}
