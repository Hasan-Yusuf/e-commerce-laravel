<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Applying a coupon to a checkout, the totals it changes, and the order it is
 * written onto: the code and price snapshot, the discount it produced, and the
 * use it is counted for. A coupon is an order-level discount, so it also stacks
 * on top of the product-level Discount already baked into each line's price.
 */
class CouponPlacementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed session id, so the requests within a test share one cart and one
     * set of checkout answers (the test client does not replay the cookie).
     */
    private const string SESSION_ID = 'CouponPlacementSession01234567890123456789';

    public function test_a_valid_coupon_lowers_the_totals_and_shows_the_code(): void
    {
        $this->signedInUser();
        Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();

        $this->applyCoupon('save10')->assertRedirect(CheckoutStep::REVIEW->url());

        $summary = app(CheckoutService::class)->summary();

        // 2 × 20.000 = 40.000, less the coupon's 10%.
        static::assertSame(4_000, $summary->cart->discountInFils);
        static::assertSame(36_000, $summary->cart->totalInFils());
        static::assertSame('SAVE10', $summary->cart->coupon?->code);

        $this
            ->asGuest()
            ->get(CheckoutStep::REVIEW->url())
            ->assertOk()
            ->assertSee('SAVE10')
            ->assertSee(__('checkout.coupon.remove'));
    }

    public function test_removing_a_coupon_restores_the_full_price(): void
    {
        $this->signedInUser();
        Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();
        $this->applyCoupon('SAVE10');

        $this->removeCoupon()->assertRedirect(CheckoutStep::REVIEW->url());

        $summary = app(CheckoutService::class)->summary();

        static::assertNull($summary->cart->coupon);
        static::assertSame(0, $summary->cart->discountInFils);
        static::assertSame(40_000, $summary->cart->totalInFils());
    }

    public function test_an_unknown_coupon_is_refused_and_changes_nothing(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();

        $this->applyCoupon('NOPE')->assertSessionHasErrors('code');

        $summary = app(CheckoutService::class)->summary();

        static::assertNull($summary->cart->coupon);
        static::assertSame(0, $summary->cart->discountInFils);
    }

    public function test_an_expired_coupon_is_refused(): void
    {
        $this->signedInUser();
        Coupon::factory()->expired()->create(['code' => 'OLD']);
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();

        $this
            ->applyCoupon('OLD')
            ->assertSessionHasErrors([
                'code' => __('checkout.coupon.expired'),
            ]);
    }

    public function test_an_exhausted_coupon_is_refused(): void
    {
        $this->signedInUser();
        Coupon::factory()->exhausted()->create(['code' => 'GONE']);
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();

        $this
            ->applyCoupon('GONE')
            ->assertSessionHasErrors([
                'code' => __('checkout.coupon.exhausted'),
            ]);
    }

    public function test_a_subtotal_below_the_minimum_is_refused(): void
    {
        $this->signedInUser();
        Coupon::factory()
            ->percentage(10)
            ->create(['code' => 'BIG', 'min_order_amount' => 100]);
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();

        $this
            ->applyCoupon('BIG')
            ->assertSessionHasErrors([
                'code' => __('checkout.coupon.min_order'),
            ]);
    }

    public function test_placing_an_order_with_a_coupon_records_the_code_and_its_discount(): void
    {
        $this->signedInUser();
        $coupon = Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();
        $this->applyCoupon('SAVE10');

        $this->place()->assertRedirect();

        $order = Order::query()->firstOrFail();

        static::assertSame($coupon->id, $order->coupon_id);
        static::assertSame('SAVE10', $order->coupon_code);

        // The money is the coupon's, on top of the lines' own prices:
        // 40.000 subtotal, 4.000 off, 36.000 to pay.
        static::assertSame(40_000, $order->subtotal_in_fils->toFils());
        static::assertSame(0, $order->tax_amount_in_fils->toFils());
        static::assertSame(0, $order->shipping_amount_in_fils->toFils());
        static::assertSame(4_000, $order->discount_amount_in_fils->toFils());
        static::assertSame(36_000, $order->total_amount_in_fils->toFils());

        // The use is counted as the order is written.
        static::assertSame(1, $coupon->refresh()->used_count);
    }

    public function test_a_coupon_stacks_on_top_of_a_product_discount(): void
    {
        $this->signedInUser();
        $product = $this->createProduct(['price_in_fils' => 20_000]);

        Discount::factory()
            ->percentage(10)
            ->create()
            ->products()
            ->attach($product);

        Coupon::factory()->fixedAmount(1)->create(['code' => 'TAKE1']);

        $this->addToCart($product, 2);
        $this->walkToReview();
        $this->applyCoupon('TAKE1');

        $this->place();

        $order = Order::query()->firstOrFail();

        // The line keeps its discounted price (2 × 18.000) and the coupon comes
        // off the already-discounted subtotal (1.000), so both reductions are
        // recorded: the line's unit price and the order's discount.
        static::assertSame(36_000, $order->subtotal_in_fils->toFils());
        static::assertSame(1_000, $order->discount_amount_in_fils->toFils());
        static::assertSame(35_000, $order->total_amount_in_fils->toFils());
        static::assertSame(
            18_000,
            $order->orderItems()->firstOrFail()->unit_price_in_fils->toFils(),
        );
    }

    public function test_an_order_placed_without_a_coupon_keeps_none(): void
    {
        $this->signedInUser();
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();

        $this->place();

        $order = Order::query()->firstOrFail();

        static::assertNull($order->coupon_id);
        static::assertNull($order->coupon_code);
        static::assertSame(0, $order->discount_amount_in_fils->toFils());
        static::assertSame(40_000, $order->total_amount_in_fils->toFils());
    }

    public function test_a_coupon_is_spent_once_its_usage_limit_is_reached(): void
    {
        $user = $this->signedInUser();
        $coupon = Coupon::factory()
            ->percentage(10)
            ->create([
                'code' => 'ONCE',
                'usage_limit' => 1,
                'used_count' => 0,
            ]);

        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();
        $this->applyCoupon('ONCE');
        $this->place();

        static::assertSame(1, $coupon->refresh()->used_count);

        // The coupon is used up, so a later attempt is refused outright.
        $this->actingAs($user);
        $this->addToCart($this->createProduct(['price_in_fils' => 20_000]), 2);
        $this->walkToReview();

        $this
            ->applyCoupon('ONCE')
            ->assertSessionHasErrors([
                'code' => __('checkout.coupon.exhausted'),
            ]);
    }

    /**
     * Answer every step, so the shopper stands on the review.
     */
    private function walkToReview(): void
    {
        $this->submitCustomerStep();
        $this->asGuest()->post(route('checkout.delivery.store'), [
            'delivery_method' => DeliveryMethod::STANDARD->value,
        ]);
        $this->submitShippingStep();
        $this->asGuest()->post(route('checkout.payment.store'), [
            'payment_method' => PaymentMethod::COD->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function place(array $overrides = []): TestResponse
    {
        return $this->asGuest()->post(
            route('checkout.place'),
            array_merge(
                ['checkout_token' => session('checkout.token')],
                $overrides,
            ),
        );
    }

    private function applyCoupon(string $code): TestResponse
    {
        return $this->asGuest()->post(route('checkout.coupon.store'), [
            'code' => $code,
        ]);
    }

    private function removeCoupon(): TestResponse
    {
        return $this->asGuest()->post(route('checkout.coupon.remove'));
    }

    private function submitCustomerStep(): TestResponse
    {
        return $this->asGuest()->post(route('checkout.customer.store'), [
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'country_id' => Country::factory()->bahrain()->create()->id,
            'phone_number' => '39000000',
        ]);
    }

    private function submitShippingStep(): TestResponse
    {
        return $this->asGuest()->post(route('checkout.shipping.store'), [
            'street_address' => '12 Road 1234',
            'city' => 'Manama',
            'state' => 'Capital',
            'country_id' => Country::factory()->bahrain()->create()->id,
            'postal_code' => '1234',
        ]);
    }

    private function signedInUser(): User
    {
        $user = User::factory()->create([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function asGuest(): static
    {
        return $this->withCookie(config('session.cookie'), self::SESSION_ID);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        return Product::factory()
            ->active()
            ->create(
                array_merge(
                    [
                        'stock' => 10,
                        'reserved_stock' => 0,
                        'low_stock_threshold' => 0,
                    ],
                    $attributes,
                ),
            );
    }

    private function addToCart(
        Product $product,
        int $quantity = 1,
    ): TestResponse {
        return $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }
}
