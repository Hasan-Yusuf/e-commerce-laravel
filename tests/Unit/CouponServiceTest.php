<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\AppliedCoupon;
use App\DTOs\CartSummary;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The coupon rules: the discount arithmetic (percentage/fixed, the maximum
 * cap, the minimum gate, the subtotal clamp), the active/exhausted boundaries,
 * the validation verdicts, and the guarded usage counter.
 */
class CouponServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private CouponService $coupons;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->coupons = app(CouponService::class);
    }

    public function test_a_percentage_coupon_takes_a_share_of_the_subtotal(): void
    {
        $coupon = Coupon::factory()->percentage(10)->create();

        static::assertSame(1_000, $coupon->discountFor(10_000));
    }

    public function test_a_fixed_coupon_takes_its_amount_off_the_subtotal(): void
    {
        $coupon = Coupon::factory()->fixedAmount(1.5)->create();

        static::assertSame(1_500, $coupon->discountFor(10_000));
    }

    public function test_a_coupon_is_capped_by_its_maximum_discount(): void
    {
        $coupon = Coupon::factory()
            ->percentage(50)
            ->create(['maximum_discount' => 2]);

        static::assertSame(2_000, $coupon->discountFor(10_000));
    }

    public function test_a_coupon_is_never_allowed_to_exceed_the_subtotal(): void
    {
        $coupon = Coupon::factory()->fixedAmount(100)->create();

        static::assertSame(10_000, $coupon->discountFor(10_000));
    }

    public function test_a_subtotal_under_the_minimum_earns_nothing(): void
    {
        $coupon = Coupon::factory()
            ->percentage(10)
            ->create(['min_order_amount' => 20]);

        static::assertSame(0, $coupon->discountFor(10_000));
    }

    public function test_a_subtotal_meeting_the_minimum_earns_the_discount(): void
    {
        $coupon = Coupon::factory()
            ->percentage(10)
            ->create(['min_order_amount' => 10]);

        static::assertSame(1_000, $coupon->discountFor(10_000));
    }

    public function test_a_coupon_with_no_limit_is_never_exhausted(): void
    {
        $coupon = Coupon::factory()->unlimited()->create(['used_count' => 999]);

        static::assertFalse($coupon->isExhausted());
    }

    public function test_a_coupon_is_exhausted_once_its_uses_reach_the_limit(): void
    {
        $below = Coupon::factory()->create([
            'usage_limit' => 3,
            'used_count' => 2,
        ]);
        $at = Coupon::factory()->create([
            'usage_limit' => 3,
            'used_count' => 3,
        ]);

        static::assertFalse($below->isExhausted());
        static::assertTrue($at->isExhausted());
    }

    public function test_a_coupon_is_active_only_inside_its_window(): void
    {
        static::assertTrue(Coupon::factory()->create()->isCurrentlyActive());
        static::assertFalse(
            Coupon::factory()->inactive()->create()->isCurrentlyActive(),
        );
        static::assertFalse(
            Coupon::factory()->upcoming()->create()->isCurrentlyActive(),
        );
        static::assertFalse(
            Coupon::factory()->expired()->create()->isCurrentlyActive(),
        );
    }

    public function test_the_currently_active_scope_agrees_with_the_model(): void
    {
        $active = Coupon::factory()->create();
        $expired = Coupon::factory()->expired()->create();
        $upcoming = Coupon::factory()->upcoming()->create();
        $inactive = Coupon::factory()->inactive()->create();

        $found = Coupon::query()->currentlyActive()->pluck('id');

        static::assertTrue($found->contains($active->id));
        static::assertFalse($found->contains($expired->id));
        static::assertFalse($found->contains($upcoming->id));
        static::assertFalse($found->contains($inactive->id));
    }

    public function test_validate_reports_the_discount_a_valid_coupon_gives(): void
    {
        $coupon = Coupon::factory()->percentage(10)->create();

        $result = $this->coupons->validate($coupon, 10_000);

        static::assertTrue($result->isValid);
        static::assertSame(1_000, $result->discountInFils);
        static::assertNull($result->errorKey);
    }

    public function test_validate_refuses_a_coupon_that_is_not_active(): void
    {
        $result = $this->coupons->validate(
            Coupon::factory()->expired()->create(),
            10_000,
        );

        static::assertFalse($result->isValid);
        static::assertSame('checkout.coupon.expired', $result->errorKey);
    }

    public function test_validate_refuses_an_exhausted_coupon(): void
    {
        $result = $this->coupons->validate(
            Coupon::factory()->exhausted()->create(),
            10_000,
        );

        static::assertFalse($result->isValid);
        static::assertSame('checkout.coupon.exhausted', $result->errorKey);
    }

    public function test_validate_refuses_a_subtotal_below_the_minimum(): void
    {
        $coupon = Coupon::factory()
            ->percentage(10)
            ->create(['min_order_amount' => 20]);

        $result = $this->coupons->validate($coupon, 10_000);

        static::assertFalse($result->isValid);
        static::assertSame('checkout.coupon.min_order', $result->errorKey);
    }

    public function test_resolve_finds_a_code_ignoring_case_and_surrounding_space(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'SAVE10']);

        static::assertTrue($this->coupons->resolve(' save10 ')?->is($coupon));
        static::assertNull($this->coupons->resolve('   '));
        static::assertNull($this->coupons->resolve('NOPE'));
    }

    public function test_a_code_is_stored_lowercase_and_read_back_uppercase(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'SaVe10']);

        static::assertSame('save10', $coupon->getRawOriginal('code'));
        static::assertSame('SAVE10', $coupon->code);
    }

    public function test_resolve_matches_codes_with_an_indexed_equality(): void
    {
        Coupon::factory()->create(['code' => 'SAVE10']);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $coupon = $this->coupons->resolve('sAvE10');

        $queries = collect(DB::getQueryLog())->pluck('query');

        DB::disableQueryLog();

        static::assertNotNull($coupon);
        static::assertTrue(
            $queries->contains(
                static fn (string $query): bool => str_contains($query, '"code" = ?'),
            ),
            'The code should be matched with a plain equality.',
        );
        static::assertFalse(
            $queries->contains(
                static fn (string $query): bool => str_contains($query, 'LOWER('),
            ),
            'The code lookup should not fold case in SQL.',
        );
    }

    public function test_apply_to_cart_folds_the_discount_into_the_totals(): void
    {
        $coupon = Coupon::factory()->percentage(10)->create();

        $applied = $this->coupons->applyToCart(
            $this->cart(10_000),
            new AppliedCoupon(
                id: $coupon->id,
                code: $coupon->code,
                discountInFils: 0,
            ),
        );

        static::assertSame(1_000, $applied->discountInFils);
        static::assertSame(9_000, $applied->totalInFils());
        static::assertSame($coupon->id, $applied->coupon?->id);
    }

    public function test_apply_to_cart_recomputes_a_stale_discount(): void
    {
        $coupon = Coupon::factory()->fixedAmount(1)->create();

        $applied = $this->coupons->applyToCart(
            $this->cart(10_000),
            // The stored discount is stale; the live one is recomputed.
            new AppliedCoupon(
                id: $coupon->id,
                code: $coupon->code,
                discountInFils: 9_999,
            ),
        );

        static::assertSame(1_000, $applied->discountInFils);
    }

    public function test_apply_to_cart_without_a_coupon_leaves_the_totals_alone(): void
    {
        $cart = $this->cart(10_000);

        static::assertSame($cart, $this->coupons->applyToCart($cart, null));
    }

    public function test_record_usage_counts_a_use(): void
    {
        $coupon = Coupon::factory()->create([
            'usage_limit' => 5,
            'used_count' => 0,
        ]);

        $this->coupons->recordUsage($coupon);

        static::assertSame(1, $coupon->refresh()->used_count);
    }

    public function test_record_usage_never_takes_a_limited_coupon_past_its_limit(): void
    {
        $coupon = Coupon::factory()->create([
            'usage_limit' => 1,
            'used_count' => 1,
        ]);

        $this->coupons->recordUsage($coupon);

        static::assertSame(1, $coupon->refresh()->used_count);
    }

    public function test_record_usage_keeps_counting_an_unlimited_coupon(): void
    {
        $coupon = Coupon::factory()->unlimited()->create(['used_count' => 5]);

        $this->coupons->recordUsage($coupon);

        static::assertSame(6, $coupon->refresh()->used_count);
    }

    private function cart(int $subtotalInFils): CartSummary
    {
        return new CartSummary(
            lines: new Collection,
            itemCount: 0,
            subtotalInFils: $subtotalInFils,
        );
    }
}
