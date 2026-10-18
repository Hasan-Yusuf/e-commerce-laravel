<?php

declare(strict_types=1);

namespace App\Services\Coupon;

use App\DTOs\AppliedCoupon;
use App\DTOs\CartSummary;
use App\DTOs\CouponValidationResult;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The coupon rules: resolving a typed code, deciding whether it applies to a
 * subtotal, folding an applied one into the cart's totals, and recording a use
 * when the order it discounted is placed.
 *
 * Coupons are order-level — they reduce the already-discounted subtotal rather
 * than each line's price — so nothing here touches per-line pricing, which
 * App\Services\Catalog\ProductPricingService already owns. Where the applied
 * coupon is kept is the checkout's business (App\Services\Checkout).
 */
class CouponService
{
    /**
     * The coupon a typed code names, ignoring case and surrounding space, or
     * null when no coupon carries it.
     *
     * Codes are stored lowercase (see Coupon::code), so the typed code is
     * normalised before an equality match rather than matched with a LOWER()
     * scan: the equality uses the code index.
     */
    public function resolve(string $code): ?Coupon
    {
        $normalized = Str::lower(trim($code));

        if ($normalized === '') {
            return null;
        }

        return Coupon::query()
            ->where('code', $normalized)
            ->first();
    }

    /**
     * Whether the coupon applies to the subtotal, and the discount it gives in
     * fils. A coupon that is not active now, is used up, or earns nothing on
     * this subtotal is refused with the reason's translation key.
     */
    public function validate(
        Coupon $coupon,
        int $subtotalInFils,
    ): CouponValidationResult {
        if (! $coupon->isCurrentlyActive()) {
            return CouponValidationResult::invalid('checkout.coupon.expired');
        }

        if ($coupon->isExhausted()) {
            return CouponValidationResult::invalid('checkout.coupon.exhausted');
        }

        $discountInFils = $coupon->discountFor($subtotalInFils);

        if ($discountInFils <= 0) {
            return CouponValidationResult::invalid('checkout.coupon.min_order');
        }

        return CouponValidationResult::valid($discountInFils);
    }

    /**
     * The cart summary with the applied coupon folded into its totals. The
     * coupon is re-resolved and re-validated against the cart as it stands now,
     * so a code that expired, ran out or no longer clears the minimum while the
     * shopper sat on the review stops discounting rather than discounting stale.
     */
    public function applyToCart(CartSummary $cart, ?AppliedCoupon $coupon): CartSummary
    {
        if ($coupon === null) {
            return $cart;
        }

        $resolved = $this->resolve($coupon->code);

        if ($resolved === null) {
            return $cart;
        }

        $result = $this->validate($resolved, $cart->subtotalInFils);

        if (! $result->isValid) {
            return $cart;
        }

        return $cart->withCoupon(
            new AppliedCoupon(
                id: (int) $resolved->getKey(),
                code: $resolved->code,
                discountInFils: $result->discountInFils,
            ),
        );
    }

    /**
     * Count a use of the coupon, in one guarded statement: a coupon with no
     * limit always increments, while a limited one only does so below its limit.
     * Doing the guard and the increment together is what keeps two concurrent
     * placements of the last allowed use from both taking it.
     */
    public function recordUsage(Coupon $coupon): void
    {
        Coupon::query()
            ->whereKey($coupon->getKey())
            ->where(
                static fn (Builder $query): Builder => $query
                    ->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit'),
            )
            ->increment('used_count');
    }
}
