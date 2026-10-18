<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CacheKeys;
use App\Enums\DashboardPeriod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * The aggregates the admin dashboard renders. Every figure is a single grouped
 * or counted query cached under a period-aware key, mirroring
 * OrderResource::getNavigationBadge(), because the dashboard is opened far more
 * often than the orders behind it change. Widgets call these and only format the
 * result, so the queries stay here and stay testable on their own.
 */
class DashboardService
{
    private const int CACHE_MINUTES = 5;

    private const int TREND_DAYS = 30;

    private const int ATTENTION_LIMIT = 10;

    private const int RECENT_ORDERS_LIMIT = 10;

    /**
     * The value of orders placed in the period, in fils. Cancelled and refunded
     * orders never brought money in, so they are left out.
     */
    public function revenueIn(DashboardPeriod $period): int
    {
        return Cache::remember(
            CacheKeys::DashboardRevenue->key($period->value),
            now()->addMinutes(self::CACHE_MINUTES),
            static fn (): int => (int) Order::query()
                ->whereNotIn('status', [
                    OrderStatus::CANCELLED,
                    OrderStatus::REFUNDED,
                ])
                ->whereBetween('created_at', [$period->start(), $period->end()])
                ->sum('total_amount_in_fils'),
        );
    }

    public function ordersToFulfilCount(): int
    {
        return Cache::remember(
            CacheKeys::DashboardOrdersToFulfil->key(),
            now()->addMinutes(self::CACHE_MINUTES),
            static fn (): int => Order::query()
                ->whereIn('status', [
                    OrderStatus::PENDING,
                    OrderStatus::PROCESSING,
                ])
                ->count(),
        );
    }

    public function lowStockProductsCount(): int
    {
        return Cache::remember(
            CacheKeys::DashboardLowStockProducts->key(),
            now()->addMinutes(self::CACHE_MINUTES),
            static fn (): int => Product::query()
                ->active()
                ->lowOnStock()
                ->count(),
        );
    }

    public function pendingPaymentsCount(): int
    {
        return Cache::remember(
            CacheKeys::DashboardPendingPayments->key(),
            now()->addMinutes(self::CACHE_MINUTES),
            static fn (): int => Payment::query()
                ->where('status', PaymentStatus::PENDING)
                ->count(),
        );
    }

    public function newCustomersIn(DashboardPeriod $period): int
    {
        return Cache::remember(
            CacheKeys::DashboardNewCustomers->key($period->value),
            now()->addMinutes(self::CACHE_MINUTES),
            static fn (): int => User::query()
                ->where('role', UserRole::CUSTOMER)
                ->whereBetween('created_at', [$period->start(), $period->end()])
                ->count(),
        );
    }

    public function unreadMessagesCount(): int
    {
        return Cache::remember(
            CacheKeys::UnreadMessagesCount->key(),
            now()->addMinutes(self::CACHE_MINUTES),
            static fn (): int => ContactMessage::query()
                ->whereNull('read_at')
                ->count(),
        );
    }

    /**
     * The daily revenue the trend chart plots, one entry per day with days that
     * had no orders filled in at zero so the line never drops a point. A single
     * grouped query backs the whole series.
     *
     * @return array<int, array{label: string, total_in_fils: int}>
     */
    public function revenuePerDay(int $days = self::TREND_DAYS): array
    {
        return Cache::remember(
            CacheKeys::DashboardRevenueTrend->key($days),
            now()->addMinutes(self::CACHE_MINUTES),
            static function () use ($days): array {
                $start = now()->subDays($days - 1)->startOfDay();

                $totals = Order::query()
                    ->whereNotIn('status', [
                        OrderStatus::CANCELLED,
                        OrderStatus::REFUNDED,
                    ])
                    ->where('created_at', '>=', $start)
                    ->selectRaw(
                        'date(created_at) as day, COALESCE(SUM(total_amount_in_fils), 0) as total_in_fils',
                    )
                    ->groupBy('day')
                    ->pluck('total_in_fils', 'day');

                $series = [];

                for ($offset = $days - 1; $offset >= 0; $offset--) {
                    $day = now()->subDays($offset)->startOfDay();

                    $series[] = [
                        'label' => $day->format('d M'),
                        'total_in_fils' => (int) $totals->get(
                            $day->toDateString(),
                            0,
                        ),
                    ];
                }

                return $series;
            },
        );
    }

    /**
     * The unpaid and unfulfilled orders, oldest first so the dashboard's
     * attention table leads with what has waited longest.
     */
    public function ordersRequiringAttentionQuery(): Builder
    {
        return Order::query()
            ->whereIn('status', [
                OrderStatus::PENDING,
                OrderStatus::PROCESSING,
            ])
            ->with('user')
            ->oldest()
            ->limit(self::ATTENTION_LIMIT);
    }

    /**
     * The active products at or below their restock threshold, thinnest first.
     */
    public function lowStockProductsQuery(): Builder
    {
        return Product::query()
            ->active()
            ->lowOnStock()
            ->withAvailableStock()
            ->orderBy('stock');
    }

    /**
     * The newest orders across every status, for the dashboard's activity list.
     */
    public function recentOrdersQuery(): Builder
    {
        return Order::query()
            ->with('user')
            ->latest()
            ->limit(self::RECENT_ORDERS_LIMIT);
    }
}
