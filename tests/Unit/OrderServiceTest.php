<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\AppliedCoupon;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string SESSION_ID = 'OrderPlacementUnit0123456789012345678901';

    private CartService $cartService;

    private OrderService $orders;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        session()->setId(self::SESSION_ID);

        $this->cartService = app(CartService::class);
        $this->orders = app(OrderService::class);
    }

    public function test_the_amounts_sum_the_lines_in_whole_fils(): void
    {
        $this->cartService->add(
            $this->createProduct(['price_in_fils' => 12_500]),
            2,
        );
        $this->cartService->add(
            $this->createProduct(['price_in_fils' => 3_750]),
            3,
        );

        $amounts = $this->orders->amounts($this->cartService->cartSummary());

        // 2 × 12.500 + 3 × 3.750 = 25.000 + 11.250
        static::assertSame(36_250, $amounts->subtotal->toFils());
        static::assertSame(0, $amounts->tax->toFils());
        static::assertSame(0, $amounts->shipping->toFils());
        static::assertSame(0, $amounts->discount->toFils());
        static::assertSame(36_250, $amounts->total->toFils());

        static::assertSame(
            [
                'subtotal_in_fils' => 36_250,
                'tax_amount_in_fils' => 0,
                'shipping_amount_in_fils' => 0,
                'discount_amount_in_fils' => 0,
                'total_amount_in_fils' => 36_250,
            ],
            array_map(
                static fn (Money $amount): int => $amount->toFils(),
                $amounts->toArray(),
            ),
        );
    }

    public function test_the_amounts_apply_the_merchants_tax_and_shipping_when_given(): void
    {
        $this->cartService->add(
            $this->createProduct(['price_in_fils' => 20_000]),
            2,
        );

        $amounts = $this->orders->amounts(
            $this->cartService->cartSummary(),
            taxPercentage: 15,
            shippingInFils: 1_000,
        );

        // 2 × 20.000 subtotal, plus 15% tax and a 1.000 flat shipping charge.
        static::assertSame(40_000, $amounts->subtotal->toFils());
        static::assertSame(6_000, $amounts->tax->toFils());
        static::assertSame(1_000, $amounts->shipping->toFils());
        static::assertSame(47_000, $amounts->total->toFils());
    }

    public function test_the_amounts_price_blank_tax_and_shipping_as_zero(): void
    {
        $this->cartService->add(
            $this->createProduct(['price_in_fils' => 20_000]),
            2,
        );

        $amounts = $this->orders->amounts(
            $this->cartService->cartSummary(),
            taxPercentage: null,
            shippingInFils: null,
        );

        static::assertSame(0, $amounts->tax->toFils());
        static::assertSame(0, $amounts->shipping->toFils());
        static::assertSame(40_000, $amounts->total->toFils());
    }

    public function test_the_amounts_are_the_prices_the_shopper_was_quoted(): void
    {
        $product = $this->createProduct(['price_in_fils' => 20_000]);

        Discount::factory()
            ->percentage(10)
            ->create()
            ->products()
            ->attach($product);

        $this->cartService->add($product, 2);

        // The discounted line, not the list price: 2 × 18.000.
        static::assertSame(
            36_000,
            $this->orders->amounts($this->cartService->cartSummary())->total->toFils(),
        );
    }

    public function test_the_amounts_carry_the_applied_coupon_discount(): void
    {
        $this->cartService->add(
            $this->createProduct(['price_in_fils' => 20_000]),
            2,
        );

        $amounts = $this->orders->amounts(
            $this->cartService->cartSummary(),
            new AppliedCoupon(id: 1, code: 'SAVE10', discountInFils: 4_000),
        );

        // The coupon comes off the subtotal on top of the line prices: 40.000
        // less 4.000, for 36.000 to pay.
        static::assertSame(40_000, $amounts->subtotal->toFils());
        static::assertSame(4_000, $amounts->discount->toFils());
        static::assertSame(36_000, $amounts->total->toFils());
    }

    public function test_the_amounts_read_the_discount_a_cart_already_carries(): void
    {
        $this->cartService->add(
            $this->createProduct(['price_in_fils' => 20_000]),
            2,
        );

        $cart = $this->cartService
            ->cartSummary()
            ->withCoupon(
                new AppliedCoupon(id: 1, code: 'SAVE10', discountInFils: 4_000),
            );

        $amounts = $this->orders->amounts($cart);

        static::assertSame(4_000, $amounts->discount->toFils());
        static::assertSame(36_000, $amounts->total->toFils());
    }

    public function test_an_empty_cart_is_worth_nothing(): void
    {
        $amounts = $this->orders->amounts($this->cartService->cartSummary());

        static::assertSame(0, $amounts->subtotal->toFils());
        static::assertSame(0, $amounts->total->toFils());
    }

    public function test_it_finds_the_order_a_checkout_token_has_already_placed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $placed = $this->orders->getOrderFrom(
            (string) $order->checkout_token,
            $user,
        );

        static::assertTrue($placed?->is($order));

        // A token belongs to the account whose checkout it is, and no token at
        // all belongs to nobody.
        static::assertNull(
            $this->orders->getOrderFrom(
                (string) $order->checkout_token,
                User::factory()->create(),
            ),
        );
        static::assertNull($this->orders->getOrderFrom('', $user));
    }

    public function test_the_confirmation_url_carries_the_number_and_the_token(): void
    {
        $order = Order::factory()->create();

        static::assertSame(
            route('checkout.confirmation', [
                'order' => $order->number,
                'token' => $order->access_token,
            ]),
            $this->orders->confirmationUrl($order),
        );
    }

    public function test_the_history_shows_as_many_orders_as_the_shopper_asks_for(): void
    {
        foreach (OrderService::PER_PAGE_OPTIONS as $option) {
            static::assertSame(
                $option,
                $this->orders->perPage((string) $option),
            );
        }

        // Asking for nothing, or for a count the page does not offer, is the
        // default rather than a number somebody typed into the URL.
        static::assertSame(
            OrderService::DEFAULT_PER_PAGE,
            $this->orders->perPage(null),
        );
        static::assertSame(
            OrderService::DEFAULT_PER_PAGE,
            $this->orders->perPage('9999'),
        );
        static::assertSame(
            OrderService::DEFAULT_PER_PAGE,
            $this->orders->perPage('nonsense'),
        );
    }

    public function test_the_overview_reads_the_newest_orders_and_no_more(): void
    {
        $user = User::factory()->create();

        $orders = collect(range(1, OrderService::RECENT_LIMIT + 1))->map(
            static fn (): Order => Order::factory()->create([
                'user_id' => $user->id,
            ]),
        );

        $recent = $this->orders->getRecentFor($user);

        static::assertCount(OrderService::RECENT_LIMIT, $recent);
        static::assertSame($orders->last()?->id, $recent->first()?->id);

        // Each row reads the payment it shows without a query of its own.
        static::assertTrue(
            $recent->every(
                static fn (Order $order): bool => $order->relationLoaded(
                    'payments',
                ),
            ),
        );
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
}
