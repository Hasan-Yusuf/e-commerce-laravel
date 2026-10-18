<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CheckoutStep;
use App\Http\Requests\Checkout\ApplyCouponRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Requests\Checkout\RemoveCouponRequest;
use App\Http\Requests\Checkout\StoreCustomerRequest;
use App\Http\Requests\Checkout\StoreDeliveryRequest;
use App\Http\Requests\Checkout\StorePaymentRequest;
use App\Http\Requests\Checkout\StoreShippingAddressRequest;
use App\Models\User;
use App\Services\Account\AddressService;
use App\Services\Checkout\CheckoutFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The checkout steps. Each step validates its own request, hands the answer to
 * the flow (which lives in the checkout state) and redirects to the step the
 * flow has moved to, so the flow is a plain POST-redirect-GET and works without
 * JavaScript.
 *
 * Every step is also addressable on its own — /checkout/customer, /checkout/
 * delivery, … — and a step the shopper has not reached yet lands them on the
 * one they should be on instead.
 */
final class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutFlow $flow,
        private AddressService $addressService,
    ) {}

    /**
     * The step the shopper is on.
     */
    public function index(): View|RedirectResponse
    {
        return $this->flow->withCart(fn (): View => $this->render($this->flow->begin()));
    }

    /**
     * The step the URL asks for.
     */
    public function show(CheckoutStep $step): View|RedirectResponse
    {
        return $this->flow->withCart(function () use ($step): View|RedirectResponse {
            $shown = $this->flow->open($step);

            return $shown === $step
                ? $this->render($shown)
                : redirect()->to($shown->url());
        });
    }

    public function storeCustomer(
        StoreCustomerRequest $request,
    ): RedirectResponse {
        return $this->flow->withCart(function () use ($request): RedirectResponse {
            $this->flow->saveCustomer($request->customer());

            return $this->next();
        });
    }

    public function storeShippingAddress(
        StoreShippingAddressRequest $request,
    ): RedirectResponse {
        return $this->flow->withCart(function () use ($request): RedirectResponse {
            $address = $request->address();

            $this->flow->saveShippingAddress($address);

            if ($request->boolean('save_to_account') && $request->user() !== null) {
                $this->addressService->saveShippingAddress($request->user(), $address);
            }

            return $this->next();
        });
    }

    public function storeDelivery(
        StoreDeliveryRequest $request,
    ): RedirectResponse {
        return $this->flow->withCart(function () use ($request): RedirectResponse {
            $this->flow->saveDeliveryMethod($request->deliveryMethod());

            return $this->next();
        });
    }

    public function storePayment(StorePaymentRequest $request): RedirectResponse
    {
        return $this->flow->withCart(function () use ($request): RedirectResponse {
            $this->flow->savePaymentMethod($request->paymentMethod());

            return $this->next();
        });
    }

    /**
     * Apply the coupon a typed code names to the review. A code that applies is
     * remembered on the checkout; one that does not is refused with its reason,
     * and the review is shown again with the code still in the field.
     */
    public function storeCoupon(ApplyCouponRequest $request): RedirectResponse
    {
        return $this->flow->withCart(function () use ($request): RedirectResponse {
            $result = $this->flow->applyCoupon($request->code());

            if (! $result->isValid) {
                return redirect()
                    ->to(CheckoutStep::getReviewStepUrl())
                    ->withErrors(['code' => __((string) $result->errorKey)])
                    ->withInput();
            }

            return redirect()
                ->to(CheckoutStep::getReviewStepUrl())
                ->with('coupon_applied', true);
        });
    }

    public function removeCoupon(RemoveCouponRequest $request): RedirectResponse
    {
        return $this->flow->withCart(function (): RedirectResponse {
            $this->flow->removeCoupon();

            return redirect()->to(CheckoutStep::getReviewStepUrl());
        });
    }

    /**
     * Place the order. The endpoint carries the `auth` middleware, so a guest
     * has been sent to sign in and returned to the review step with the cart and
     * the answers intact.
     */
    public function place(PlaceOrderRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->flow->place($user, $request->token(), $request->note());
    }

    private function render(CheckoutStep $step): View
    {
        if ($step->isReview() && Auth::guest()) {
            // Reviewing as a guest: both the sign-in links on the review and the
            // auth gate on placing the order bring the shopper back here, so
            // that signing in mid-checkout resumes the flow instead of losing it.
            redirect()->setIntendedUrl($step->url());
        }

        return view('checkout.index', [
            'step' => $step,
            'steps' => $this->flow->steps(),
            'stepNumber' => $this->flow->numberOf($step),
            'previousStep' => $this->flow->previousStep($step),
            'summary' => $this->flow->summary(),
        ]);
    }

    /**
     * On to the step the flow has moved to.
     */
    private function next(): RedirectResponse
    {
        return redirect()->to($this->flow->currentStepUrl());
    }
}
