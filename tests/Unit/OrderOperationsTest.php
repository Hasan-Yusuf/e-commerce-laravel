<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\CheckoutState;
use App\DTOs\OrderPlacementResult;
use App\DTOs\OrderProcessingContext;
use App\DTOs\ShippingAddressData;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockWarningNotification;
use App\Pipelines\Orders\Pipes\CreateOrder;
use App\Pipelines\Orders\Pipes\LockProducts;
use App\Pipelines\Orders\Pipes\VerifyStock;
use App\Pipelines\Orders\PipesRan;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Tests\TestCase;

/**
 * The operations an order goes through — placing it, and each move between
 * statuses — driven the way the application drives them, through OrderService.
 *
 * They are asserted on what they leave behind: the status, the stock on hand,
 * the hold on the shelf and the payment, rather than on the steps taken, which
 * is also what catches a pipe that stopped the chain early.
 */
class OrderOperationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string SESSION_ID = 'OrderOperationsUnit01234567890123456789';

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

    public function test_placing_writes_the_order_its_lines_its_payment_and_its_hold_together(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->bahrain()->create();

        $product = $this->makeProduct(
            price: 12_500,
            stock: 10,
            lowStockThreshold: 0,
        );

        $this->cartService->add($product, 3);

        $result = $this->orders->placeOrder(
            $this->checkoutState($country->id),
            $this->cartService->cartSummary(),
            $user,
            'Please call before delivery.',
        );

        static::assertTrue($result->wasPlaced());

        $order = $result->order;

        static::assertSame($user->id, $order?->user_id);
        static::assertSame(OrderStatus::PENDING, $order->status);
        static::assertSame('Please call before delivery.', $order->notes);
        static::assertSame(37_500, $order->subtotal_in_fils->toFils());
        static::assertSame(37_500, $order->total_amount_in_fils->toFils());
        static::assertTrue($order->isShippingBillingSame());

        $item = $order->orderItems()->sole();
        static::assertSame($product->id, $item->product_id);
        static::assertSame(3, (int) $item->quantity);

        $payment = $order->payments()->sole();
        static::assertSame(PaymentMethod::COD, $payment->method);
        static::assertSame(PaymentStatus::PENDING, $payment->status);
        static::assertSame(37_500, $payment->amount_in_fils->toFils());

        // The hold the lines imply is derived from them: 3 of the 10 reserved.
        static::assertSame(3, $product->refresh()->reserved_stock);
        static::assertSame(7, $product->getAvailableStock());
    }

    public function test_placing_refuses_a_checkout_the_shopper_has_not_answered(): void
    {
        $user = User::factory()->create();

        $this->cartService->add($this->makeProduct(), 1);

        // The flow turns an unanswered checkout away before it places, and the
        // pipeline guards the same invariant rather than writing an order the
        // shopper never finished.
        try {
            $this->orders->placeOrder(
                new CheckoutState,
                $this->cartService->cartSummary(),
                $user,
            );

            static::fail('The placement should have refused an unanswered checkout.');
        } catch (LogicException $exception) {
            static::assertStringContainsString(
                'unanswered checkout',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_writes_nothing_when_the_stock_moved_under_the_shopper(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->bahrain()->create();
        $product = $this->makeProduct(stock: 10, lowStockThreshold: 0);

        $this->cartService->add($product, 4);

        // Most of it is sold between the cart being filled and the placement.
        $product->update(['stock' => 2]);

        $result = $this->orders->placeOrder(
            $this->checkoutState($country->id),
            $this->cartService->cartSummary(),
            $user,
        );

        static::assertFalse($result->wasPlaced());
        static::assertTrue($result->stockChanged);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        static::assertSame(0, $product->refresh()->reserved_stock);
    }

    public function test_confirming_leaves_the_stock_the_hold_and_the_status_alone(): void
    {
        $product = $this->makeProduct(stock: 100, reservedStock: 25);
        $order = $this->makeOrder(OrderStatus::PENDING, $product, 25);

        $this->orders->confirmOrder($order);

        static::assertSame(OrderStatus::PROCESSING, $order->refresh()->status);

        // The order is still being prepared, so its line still holds the shelf.
        static::assertSame(100, $product->refresh()->stock);
        static::assertSame(25, $product->reserved_stock);
    }

    public function test_fulfilling_takes_the_goods_off_the_shelf_and_releases_the_hold(): void
    {
        // Two open orders hold 30 between them; only the second keeps holding
        // anything once the first ships.
        $product = $this->makeProduct(stock: 100, reservedStock: 30);

        $order = $this->makeOrder(OrderStatus::PROCESSING, $product, 25);
        $this->makeOrder(OrderStatus::PENDING, $product, 5);

        $this->orders->fulfilOrder($order, [
            'status' => OrderStatus::DELIVERED->value,
            'delivered_at' => '2026-09-30 09:00:00',
        ]);

        $order->refresh();

        static::assertSame(OrderStatus::DELIVERED, $order->status);
        static::assertSame(
            '2026-09-30 09:00:00',
            $order->delivered_at->format('Y-m-d H:i:s'),
        );

        static::assertSame(75, $product->refresh()->stock);
        static::assertSame(5, $product->reserved_stock);
    }

    public function test_fulfilling_an_order_to_be_delivered_ships_it(): void
    {
        $product = $this->makeProduct(stock: 100, reservedStock: 25);
        $order = $this->makeOrder(
            OrderStatus::PROCESSING,
            $product,
            25,
            DeliveryMethod::STANDARD,
        );

        $this->orders->fulfilOrder($order);

        $order->refresh();

        static::assertSame(OrderStatus::SHIPPED, $order->status);
        static::assertNull($order->delivered_at);
    }

    public function test_fulfilling_an_order_to_be_collected_marks_it_delivered(): void
    {
        $product = $this->makeProduct(stock: 100, reservedStock: 25);
        $order = $this->makeOrder(
            OrderStatus::PROCESSING,
            $product,
            25,
            DeliveryMethod::PICKUP,
        );

        $this->orders->fulfilOrder($order);

        $order->refresh();

        // Collected in the shop, so it is delivered rather than shipped.
        static::assertSame(OrderStatus::DELIVERED, $order->status);
        static::assertNotNull($order->delivered_at);
    }

    public function test_fulfilling_twice_leaves_the_stock_correct_exactly_once(): void
    {
        $product = $this->makeProduct(stock: 100, reservedStock: 25);
        $order = $this->makeOrder(OrderStatus::PENDING, $product, 25);

        $this->orders->fulfilOrder($order);
        $this->orders->fulfilOrder($order);

        $order->refresh();

        static::assertSame(OrderStatus::SHIPPED, $order->status);
        static::assertSame(75, $product->refresh()->stock);
        static::assertSame(0, $product->reserved_stock);
    }

    public function test_fulfilling_reports_the_products_it_leaves_low(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $product = $this->makeProduct(
            stock: 100,
            reservedStock: 25,
            lowStockThreshold: 80,
        );
        $order = $this->makeOrder(OrderStatus::PENDING, $product, 25);

        $this->orders->fulfilOrder($order);

        Notification::assertSentTo($admin, LowStockWarningNotification::class);
        Notification::assertNotSentTo(
            $customer,
            LowStockWarningNotification::class,
        );
    }

    public function test_fulfilling_reports_nothing_when_the_shelf_stays_stocked(): void
    {
        Notification::fake();

        User::factory()->create(['role' => UserRole::ADMIN]);

        $product = $this->makeProduct(
            stock: 100,
            reservedStock: 25,
            lowStockThreshold: 5,
        );
        $order = $this->makeOrder(OrderStatus::PENDING, $product, 25);

        $this->orders->fulfilOrder($order);

        Notification::assertNothingSent();
    }

    public function test_cancelling_releases_the_hold_and_leaves_the_goods_on_the_shelf(): void
    {
        $product = $this->makeProduct(stock: 100, reservedStock: 25);
        $order = $this->makeOrder(OrderStatus::PROCESSING, $product, 25);

        $this->orders->cancelOrder($order);

        static::assertSame(OrderStatus::CANCELLED, $order->refresh()->status);
        static::assertSame(100, $product->refresh()->stock);
        static::assertSame(0, $product->reserved_stock);
    }

    public function test_refunding_puts_the_goods_back(): void
    {
        $product = $this->makeProduct(stock: 75, reservedStock: 0);
        $order = $this->makeOrder(OrderStatus::SHIPPED, $product, 25);

        $this->orders->refundOrder($order);

        static::assertSame(OrderStatus::REFUNDED, $order->refresh()->status);
        static::assertSame(100, $product->refresh()->stock);
        static::assertSame(0, $product->reserved_stock);
    }

    public function test_reinstating_holds_the_goods_again_and_reports_what_it_drains(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $product = $this->makeProduct(
            stock: 100,
            reservedStock: 0,
            lowStockThreshold: 95,
        );
        $order = $this->makeOrder(OrderStatus::CANCELLED, $product, 10);

        $this->orders->reinstateOrder($order);

        static::assertSame(OrderStatus::PENDING, $order->refresh()->status);
        static::assertSame(100, $product->refresh()->stock);
        static::assertSame(10, $product->reserved_stock);

        Notification::assertSentTo($admin, LowStockWarningNotification::class);
    }

    public function test_a_transition_the_order_cannot_make_changes_nothing(): void
    {
        $product = $this->makeProduct(stock: 75, reservedStock: 0);
        $order = $this->makeOrder(OrderStatus::SHIPPED, $product, 25);

        $this->orders->fulfilOrder($order);
        $this->orders->cancelOrder($order);

        static::assertSame(OrderStatus::SHIPPED, $order->refresh()->status);
        static::assertSame(75, $product->refresh()->stock);
    }

    public function test_reserving_derives_the_hold_an_order_written_by_hand_takes(): void
    {
        $product = $this->makeProduct(stock: 100, reservedStock: 0);
        $order = $this->makeOrder(OrderStatus::PENDING, $product, 4);

        // Nothing held the shelf when the line was written directly...
        static::assertSame(0, $product->refresh()->reserved_stock);

        $this->orders->reserveOrder($order);

        static::assertSame(4, $product->refresh()->reserved_stock);

        // ...and deriving it again lands on the same figure.
        $this->orders->reserveOrder($order);

        static::assertSame(4, $product->refresh()->reserved_stock);
    }

    public function test_a_chain_that_stops_without_saying_so_is_refused(): void
    {
        $context = (new OrderProcessingContext)->recordPipe(LockProducts::class);

        $this->expectException(LogicException::class);

        PipesRan::ensure($context, [
            LockProducts::class,
            VerifyStock::class,
            CreateOrder::class,
        ]);
    }

    public function test_a_chain_the_shoppers_stock_stopped_on_purpose_is_accepted(): void
    {
        $context = (new OrderProcessingContext)
            ->recordPipe(LockProducts::class)
            ->recordPipe(VerifyStock::class)
            ->withResult(OrderPlacementResult::stockChanged());

        PipesRan::ensure($context, [
            LockProducts::class,
            VerifyStock::class,
            CreateOrder::class,
        ]);

        // The chain stopped on purpose, and it is the stock that stopped it.
        static::assertTrue($context->result?->stockChanged);
    }

    private function checkoutState(int $countryId): CheckoutState
    {
        $state = new CheckoutState(
            shippingAddress: new ShippingAddressData(
                streetAddress: '12 Road 1234',
                city: 'Manama',
                state: 'Capital',
                countryId: $countryId,
                postalCode: '1234',
            ),
            deliveryMethod: DeliveryMethod::STANDARD,
            paymentMethod: PaymentMethod::COD,
        );

        // The pipeline only places a checkout the shopper has answered, so the
        // state stands in for a finished flow: every step it asks for is done.
        return $state->withProgress(\count($state->plan->required));
    }

    private function makeProduct(
        int $price = 10_000,
        int $stock = 100,
        int $reservedStock = 0,
        int $lowStockThreshold = 5,
    ): Product {
        return Product::factory()
            ->active()
            ->create([
                'price_in_fils' => $price,
                'stock' => $stock,
                'reserved_stock' => $reservedStock,
                'low_stock_threshold' => $lowStockThreshold,
            ]);
    }

    private function makeOrder(
        OrderStatus $status,
        Product $product,
        int $quantity,
        DeliveryMethod $deliveryMethod = DeliveryMethod::STANDARD,
    ): Order {
        $order = Order::factory()->create([
            'status' => $status,
            'delivery_method' => $deliveryMethod,
        ]);

        OrderItem::factory()
            ->withOrder($order)
            ->withProduct($product)
            ->create(['quantity' => $quantity]);

        return $order;
    }
}
