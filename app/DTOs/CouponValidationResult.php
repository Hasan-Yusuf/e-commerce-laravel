<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Whether a coupon applies to a subtotal, and — when it does — the discount it
 * gives in fils. When it does not, errorKey names the translation key of the
 * reason to show the shopper.
 */
final readonly class CouponValidationResult
{
    private function __construct(
        public bool $isValid,
        public int $discountInFils = 0,
        public ?string $errorKey = null,
    ) {}

    public static function valid(int $discountInFils): self
    {
        return new self(true, $discountInFils);
    }

    public static function invalid(string $errorKey): self
    {
        return new self(false, errorKey: $errorKey);
    }
}
