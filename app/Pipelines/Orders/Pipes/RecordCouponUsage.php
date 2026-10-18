<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\OrderProcessingContext;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use Closure;

/**
 * Count the use of the coupon the order consumed. The order is the record of
 * what was discounted, so the coupon is read back off it rather than off the
 * cart, and the count happens in the same transaction that wrote the order —
 * so the limit it was checked against still holds when its use is counted.
 */
class RecordCouponUsage
{
    public function __construct(
        private CouponService $couponService,
    ) {}

    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $coupon = $this->couponFor($context);

        if ($coupon instanceof Coupon) {
            $this->couponService->recordUsage($coupon);
        }

        return $next($context->recordPipe(self::class));
    }

    private function couponFor(OrderProcessingContext $context): ?Coupon
    {
        $couponId = $context->orderOrFail()->coupon_id;

        if ($couponId === null) {
            return null;
        }

        return Coupon::query()->find($couponId);
    }
}
