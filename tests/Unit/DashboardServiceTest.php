<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DashboardPeriod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\Money;
use DateTimeInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard aggregates. Orders are given an explicit subtotal with tax,
 * shipping and discount zero so their stored total is the figure the tests
 * speak in, and every qualifying order is created before the service is asked,
 * because the results are cached for the request.
 */
class DashboardServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private DashboardService $dashboardService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboardService = app(DashboardService::class);
    }

    public function test_revenue_in_sums_the_period_and_excludes_cancelled_and_refunded(): void
    {
        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());
        $this->orderWithTotal(100, OrderStatus::SHIPPED, now()->subDay());
        $this->orderWithTotal(500, OrderStatus::CANCELLED, now());
        $this->orderWithTotal(500, OrderStatus::REFUNDED, now());
        $this->orderWithTotal(900, OrderStatus::DELIVERED, now()->subDays(40));

        static::assertSame(
            200_000,
            $this->dashboardService->revenueIn(DashboardPeriod::THIRTY_DAYS),
        );
    }

    public function test_revenue_in_narrows_to_the_selected_period(): void
    {
        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());
        $this->orderWithTotal(100, OrderStatus::DELIVERED, now()->subDays(10));

        static::assertSame(
            100_000,
            $this->dashboardService->revenueIn(DashboardPeriod::TODAY),
        );

        static::assertSame(
            200_000,
            $this->dashboardService->revenueIn(DashboardPeriod::THIRTY_DAYS),
        );
    }

    public function test_an_aggregate_is_cached_for_its_period(): void
    {
        static::assertSame(
            0,
            $this->dashboardService->revenueIn(DashboardPeriod::TODAY),
        );

        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());

        static::assertSame(
            0,
            $this->dashboardService->revenueIn(DashboardPeriod::TODAY),
        );
    }

    public function test_orders_to_fulfil_counts_only_pending_and_processing(): void
    {
        Order::factory()->create(['status' => OrderStatus::PENDING]);
        Order::factory()->create(['status' => OrderStatus::PROCESSING]);
        Order::factory()->create(['status' => OrderStatus::SHIPPED]);
        Order::factory()->create(['status' => OrderStatus::DELIVERED]);
        Order::factory()->create(['status' => OrderStatus::CANCELLED]);
        Order::factory()->create(['status' => OrderStatus::REFUNDED]);

        static::assertSame(2, $this->dashboardService->ordersToFulfilCount());
    }

    public function test_low_stock_products_counts_active_products_at_or_below_the_threshold(): void
    {
        Product::factory()
            ->active()
            ->create([
                'stock' => 3,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        Product::factory()
            ->active()
            ->create([
                'stock' => 0,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        Product::factory()
            ->active()
            ->create([
                'stock' => 50,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        Product::factory()->create([
            'stock' => 0,
            'reserved_stock' => 0,
            'low_stock_threshold' => 5,
            'activated_at' => null,
        ]);

        static::assertSame(2, $this->dashboardService->lowStockProductsCount());
    }

    public function test_pending_payments_counts_only_pending_rows(): void
    {
        $order = Order::factory()->create();

        Payment::factory()->for($order)->create([
            'status' => PaymentStatus::PENDING,
        ]);
        Payment::factory()->for($order)->create([
            'status' => PaymentStatus::PENDING,
        ]);
        Payment::factory()->for($order)->paid()->create();

        static::assertSame(2, $this->dashboardService->pendingPaymentsCount());
    }

    public function test_new_customers_in_counts_only_customers_registered_in_the_period(): void
    {
        User::factory()->create([
            'role' => UserRole::CUSTOMER,
            'created_at' => now(),
        ]);
        User::factory()->create([
            'role' => UserRole::CUSTOMER,
            'created_at' => now()->subDays(10),
        ]);
        User::factory()->create([
            'role' => UserRole::CUSTOMER,
            'created_at' => now()->subDays(40),
        ]);
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'created_at' => now(),
        ]);

        static::assertSame(
            1,
            $this->dashboardService->newCustomersIn(DashboardPeriod::TODAY),
        );

        static::assertSame(
            2,
            $this->dashboardService->newCustomersIn(DashboardPeriod::THIRTY_DAYS),
        );
    }

    public function test_unread_messages_counts_only_unread_rows(): void
    {
        ContactMessage::factory()->create(['read_at' => null]);
        ContactMessage::factory()->create(['read_at' => null]);
        ContactMessage::factory()->create(['read_at' => now()]);

        static::assertSame(2, $this->dashboardService->unreadMessagesCount());
    }

    public function test_revenue_per_day_returns_one_entry_per_day_and_totals_by_date(): void
    {
        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());
        $this->orderWithTotal(50, OrderStatus::DELIVERED, now()->subDays(5));
        $this->orderWithTotal(900, OrderStatus::DELIVERED, now()->subDays(40));

        $series = $this->dashboardService->revenuePerDay();

        static::assertCount(30, $series);

        $byLabel = static fn (string $label): int => (int) collect($series)
            ->firstWhere('label', $label)['total_in_fils'];

        static::assertSame(100_000, $byLabel(now()->format('d M')));
        static::assertSame(50_000, $byLabel(now()->subDays(5)->format('d M')));
        static::assertSame(
            150_000,
            array_sum(array_column($series, 'total_in_fils')),
        );
    }

    public function test_the_attention_query_lists_unfulfilled_orders(): void
    {
        $pending = Order::factory()->create(['status' => OrderStatus::PENDING]);
        $processing = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
        ]);
        Order::factory()->create(['status' => OrderStatus::DELIVERED]);

        $ids = $this->dashboardService
            ->ordersRequiringAttentionQuery()
            ->pluck('id')
            ->all();

        static::assertEqualsCanonicalizing(
            [$pending->id, $processing->id],
            $ids,
        );
    }

    private function orderWithTotal(
        float $total,
        OrderStatus $status,
        DateTimeInterface $createdAt,
    ): Order {
        return Order::factory()->create([
            'status' => $status,
            'created_at' => $createdAt,
            'subtotal_in_fils' => Money::fromBHD($total),
            'tax_amount_in_fils' => 0,
            'shipping_amount_in_fils' => 0,
            'discount_amount_in_fils' => 0,
        ]);
    }
}
