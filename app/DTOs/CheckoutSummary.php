<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;

final readonly class CheckoutSummary
{
    public function __construct(
        public CustomerData $customer,
        public ShippingAddressData $shippingAddress,
        public DeliveryMethod $deliveryMethod,
        public PaymentMethod $paymentMethod,
        public CartSummary $cart,

        // The checkout attempt's own identity, which the review sends back with
        // the order so a repeat submission cannot place a second one.
        #[\SensitiveParameter]
        public string $token,
    ) {}

    public static function fromCheckout(
        CheckoutState $checkout,
        CartSummary $cart,
    ): self {
        return new self(
            customer: $checkout->customer,
            shippingAddress: $checkout->shippingAddress,
            deliveryMethod: $checkout->deliveryMethod,
            paymentMethod: $checkout->paymentMethod,
            cart: $cart,
            token: $checkout->token,
        );
    }
}
