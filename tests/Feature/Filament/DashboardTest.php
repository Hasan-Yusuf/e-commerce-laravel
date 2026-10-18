<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\DashboardPeriod;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\OrdersRequiringAttention;
use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\RevenueChart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin panel coverage for the management dashboard. Each widget is rendered on
 * its own so its contents can be asserted without the page's other queries, and
 * the page itself is rendered once to prove the widgets are wired in.
 */
class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_dashboard_page_renders_every_widget(): void
    {
        $this->actingAsAdmin();

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSeeLivewire(DashboardStatsOverview::class)
            ->assertSeeLivewire(RevenueChart::class)
            ->assertSeeLivewire(OrdersRequiringAttention::class)
            ->assertSeeLivewire(LowStockProducts::class)
            ->assertSeeLivewire(RecentOrders::class);
    }

    public function test_the_stats_overview_reports_the_revenue_and_links_to_the_period(): void
    {
        $this->actingAsAdmin();

        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());

        $period = DashboardPeriod::THIRTY_DAYS;

        Livewire::test(DashboardStatsOverview::class, [
            'pageFilters' => ['period' => $period->value],
        ])
            ->assertOk()
            ->assertSee(Number::currency(100, 'BHD'))
            ->assertSee(
                OrderResource::getUrl('index', [
                    'tableFilters' => ['created_at' => [
                        'from' => $period->start()->toDateString(),
                        'until' => $period->end()->toDateString(),
                    ]],
                ]),
            );
    }

    public function test_the_stats_overview_links_the_attention_figures_to_their_filters(): void
    {
        $this->actingAsAdmin();

        Livewire::test(DashboardStatsOverview::class)
            ->assertOk()
            ->assertSee(
                OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['values' => [
                            OrderStatus::PENDING->value,
                            OrderStatus::PROCESSING->value,
                        ]],
                    ],
                ]),
            )
            ->assertSee(
                ProductResource::getUrl('index', [
                    'tableFilters' => ['stock_status' => ['value' => 'low_stock']],
                ]),
            );
    }

    public function test_the_revenue_stat_follows_the_period_filter(): void
    {
        $this->actingAsAdmin();

        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());
        $this->orderWithTotal(100, OrderStatus::DELIVERED, now()->subDays(10));

        Livewire::test(DashboardStatsOverview::class, [
            'pageFilters' => ['period' => DashboardPeriod::TODAY->value],
        ])->assertSee(Number::currency(100, 'BHD'));

        Livewire::test(DashboardStatsOverview::class, [
            'pageFilters' => ['period' => DashboardPeriod::THIRTY_DAYS->value],
        ])->assertSee(Number::currency(200, 'BHD'));
    }

    public function test_the_stats_and_chart_widgets_do_not_poll(): void
    {
        $this->actingAsAdmin();

        Livewire::test(DashboardStatsOverview::class)
            ->assertDontSee('wire:poll', escape: false);

        Livewire::test(RevenueChart::class)
            ->assertDontSee('wire:poll', escape: false);
    }

    public function test_the_period_filter_persists_in_the_session(): void
    {
        $this->actingAsAdmin();

        $component = Livewire::test(Dashboard::class)
            ->set('filters.period', DashboardPeriod::TODAY->value)
            ->assertSet('filters.period', DashboardPeriod::TODAY->value);

        $sessionKey = $component->instance()->getFiltersSessionKey();

        static::assertSame(
            DashboardPeriod::TODAY->value,
            session($sessionKey)['period'] ?? null,
        );
    }

    public function test_the_orders_requiring_attention_table_lists_only_unfulfilled_orders(): void
    {
        $this->actingAsAdmin();

        $pending = Order::factory()->create(['status' => OrderStatus::PENDING]);
        $processing = Order::factory()->create([
            'status' => OrderStatus::PROCESSING,
        ]);
        $delivered = Order::factory()->create([
            'status' => OrderStatus::DELIVERED,
        ]);

        Livewire::test(OrdersRequiringAttention::class)
            ->assertCanSeeTableRecords([$pending, $processing])
            ->assertCanNotSeeTableRecords([$delivered]);
    }

    public function test_an_order_can_be_fulfilled_from_the_dashboard(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create([
            'stock' => 100,
            'reserved_stock' => 30,
            'low_stock_threshold' => 5,
        ]);

        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        OrderItem::factory()
            ->withOrder($order)
            ->withProduct($product)
            ->create(['quantity' => 25]);

        Livewire::test(OrdersRequiringAttention::class)
            ->callAction(TestAction::make('fulfil')->table($order))
            ->assertNotified();

        static::assertSame(OrderStatus::SHIPPED, $order->refresh()->status);
    }

    public function test_the_low_stock_table_lists_only_products_at_or_below_the_threshold(): void
    {
        $this->actingAsAdmin();

        $low = Product::factory()
            ->active()
            ->create([
                'stock' => 2,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        $fine = Product::factory()
            ->active()
            ->create([
                'stock' => 50,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        Livewire::test(LowStockProducts::class)
            ->assertCanSeeTableRecords([$low])
            ->assertCanNotSeeTableRecords([$fine]);
    }

    public function test_a_product_can_be_restocked_from_the_dashboard(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()
            ->active()
            ->create([
                'stock' => 2,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        Livewire::test(LowStockProducts::class)
            ->callAction(
                TestAction::make('restock')->table($product),
                ['quantity' => 10],
            )
            ->assertNotified();

        static::assertSame(12, $product->refresh()->stock);
    }

    public function test_the_recent_orders_table_lists_the_latest_orders_only(): void
    {
        $this->actingAsAdmin();

        $oldest = Order::factory()->create([
            'created_at' => now()->subDays(30),
        ]);
        $recent = Order::factory()
            ->count(10)
            ->create([
                'created_at' => now(),
            ]);

        Livewire::test(RecentOrders::class)
            ->assertCanSeeTableRecords($recent)
            ->assertCanNotSeeTableRecords([$oldest]);
    }

    public function test_the_attention_table_runs_a_constant_number_of_queries(): void
    {
        $this->actingAsAdmin();

        Order::factory()->create(['status' => OrderStatus::PENDING]);

        $single = $this->queriesFor(
            static fn () => Livewire::test(OrdersRequiringAttention::class),
        );

        Order::factory()->count(14)->create(['status' => OrderStatus::PROCESSING]);

        $many = $this->queriesFor(
            static fn () => Livewire::test(OrdersRequiringAttention::class),
        );

        static::assertSame($single, $many);
    }

    public function test_the_low_stock_table_runs_a_constant_number_of_queries(): void
    {
        $this->actingAsAdmin();

        Product::factory()
            ->active()
            ->create([
                'stock' => 2,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        $single = $this->queriesFor(
            static fn () => Livewire::test(LowStockProducts::class),
        );

        Product::factory()
            ->active()
            ->count(14)
            ->create([
                'stock' => 1,
                'reserved_stock' => 0,
                'low_stock_threshold' => 5,
            ]);

        $many = $this->queriesFor(
            static fn () => Livewire::test(LowStockProducts::class),
        );

        static::assertSame($single, $many);
    }

    public function test_the_revenue_chart_runs_a_constant_number_of_queries(): void
    {
        $this->actingAsAdmin();

        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());

        $single = $this->queriesFor(
            static fn () => Livewire::test(RevenueChart::class),
        );

        $this->orderWithTotal(100, OrderStatus::DELIVERED, now());

        $many = $this->queriesFor(
            static fn () => Livewire::test(RevenueChart::class),
        );

        static::assertSame($single, $many);
    }

    /**
     * The number of queries a callback runs, with the dashboard's caches cleared
     * first so a cached figure does not mask the query behind it.
     */
    private function queriesFor(callable $callback): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function orderWithTotal(
        float $total,
        OrderStatus $status,
        mixed $createdAt,
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

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    }
}
