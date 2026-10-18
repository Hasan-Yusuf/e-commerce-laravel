<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The steps of the checkout, in the order they are answered. The delivery is
 * asked before the address because it decides whether an address is needed at
 * all: a shopper collecting the order from the shop has nowhere to be delivered
 * to, so the address step is lifted from the flow (see CheckoutState). The
 * review is the last one: it is not a form, but it is where the flow lands once
 * every form step has been answered.
 *
 * The flow's shape lives here rather than in the service, so a step's name and
 * the URL that shows it are read from one place instead of being written out by
 * hand at each call site. Which of those steps a given checkout actually
 * requires — and so the number each one prints — is the state's business, not
 * the enum's.
 */
enum CheckoutStep: string
{
    case CUSTOMER = 'customer';
    case DELIVERY = 'delivery';
    case SHIPPING_ADDRESS = 'shipping_address';
    case PAYMENT = 'payment';
    case REVIEW = 'review';

    /**
     * The steps a form answers, in order. The review is not one of them.
     *
     * @return array<int, self>
     */
    public static function formSteps(): array
    {
        return array_values(
            array_filter(
                self::cases(),
                static fn (self $step): bool => ! $step->isReview(),
            ),
        );
    }

    public static function firstStep(): self
    {
        return self::cases()[0];
    }

    public static function getReviewStepUrl(): string
    {
        return self::REVIEW->url();
    }

    public function isReview(): bool
    {
        return $this === self::REVIEW;
    }

    /**
     * The step's name.
     */
    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => __('checkout.steps.customer.title'),
            self::SHIPPING_ADDRESS => __(
                'checkout.steps.shipping_address.title',
            ),
            self::DELIVERY => __('checkout.steps.delivery.title'),
            self::PAYMENT => __('checkout.steps.payment.title'),
            self::REVIEW => __('checkout.steps.review.title'),
        };
    }

    /**
     * The URL that shows the step: each step is addressed by its own name.
     */
    public function url(): string
    {
        return route('checkout.step', ['step' => $this->value]);
    }

    /**
     * The partial that renders the step's form (never the review's).
     */
    public function view(): string
    {
        return 'checkout.steps.'.$this->value;
    }
}
