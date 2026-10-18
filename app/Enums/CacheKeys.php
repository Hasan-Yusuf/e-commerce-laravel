<?php

declare(strict_types=1);

namespace App\Enums;

enum CacheKeys: string
{
    case UnreadMessagesCount = 'unread_contact_messages_count';
    case LowStockProductsCount = 'low_stock_products_count';
    case ApplicableProductDiscounts = 'applicable_product_discounts';
    case SpecFilterValues = 'spec_filter_values';
    case CategoryDescendantIds = 'catalog.category_descendant_ids';

    case DashboardRevenue = 'dashboard_revenue';
    case DashboardOrdersToFulfil = 'dashboard_orders_to_fulfil';
    case DashboardLowStockProducts = 'dashboard_low_stock_products';
    case DashboardPendingPayments = 'dashboard_pending_payments';
    case DashboardNewCustomers = 'dashboard_new_customers';
    case DashboardRevenueTrend = 'dashboard_revenue_trend';

    /**
     * Appends the parts a figure varies by — a period, a window of days — so a
     * cached value cannot collide with its siblings, e.g.
     * `CacheKeys::DashboardRevenue->key('today')` yields
     * `dashboard_revenue:today`.
     */
    public function key(string|int ...$parts): string
    {
        return implode(':', [$this->value, ...$parts]);
    }
}
