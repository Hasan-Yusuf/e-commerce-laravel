<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\DashboardPeriod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SemanticColor;
use App\Enums\StockStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Users\UserResource;
use App\Services\DashboardService;
use App\Support\HasTranslationKeys;
use App\Support\Money;
use App\Support\TranslationKey;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * The four attention figures (plus the two secondary ones) an administrator
 * opens the panel for. Each links straight to the resource that answers it,
 * pre-filtered, so a number is never a dead end. Polling is off: these counts
 * change far too slowly for a five-second refresh to be worth the queries.
 */
class DashboardStatsOverview extends StatsOverviewWidget
{
    use HasTranslationKeys;
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $period = DashboardPeriod::fromFilter($this->pageFilters['period'] ?? null);
        $dashboardService = app(DashboardService::class);

        return [
            Stat::make(
                self::trans('stats.revenue'),
                Number::currency(Money::fromFils($dashboardService->revenueIn($period))->toAmount(), 'BHD'),
            )
                ->description(self::trans('stats.revenue_description'))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color(SemanticColor::Success->value)
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'created_at' => [
                            'from' => $period->start()->toDateString(),
                            'until' => $period->end()->toDateString(),
                        ],
                    ],
                ])),

            Stat::make(
                self::trans('stats.orders_to_fulfil'),
                $dashboardService->ordersToFulfilCount(),
            )
                ->description(self::trans('stats.orders_to_fulfil_description'))
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color(SemanticColor::Warning->value)
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['values' => [
                            OrderStatus::PENDING->value,
                            OrderStatus::PROCESSING->value,
                        ]],
                    ],
                ])),

            Stat::make(
                self::trans('stats.low_stock'),
                $dashboardService->lowStockProductsCount(),
            )
                ->description(self::trans('stats.low_stock_description'))
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color(SemanticColor::Warning->value)
                ->url(ProductResource::getUrl('index', [
                    'tableFilters' => [
                        'stock_status' => ['value' => StockStatus::LOW_STOCK->value],
                    ],
                ])),

            Stat::make(
                self::trans('stats.uncollected_payments'),
                $dashboardService->pendingPaymentsCount(),
            )
                ->description(self::trans('stats.uncollected_payments_description'))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color(SemanticColor::Info->value)
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'payment_status' => ['value' => PaymentStatus::PENDING->value],
                    ],
                ])),

            Stat::make(
                self::trans('stats.new_customers'),
                $dashboardService->newCustomersIn($period),
            )
                ->description(self::trans('stats.new_customers_description'))
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->color(SemanticColor::Primary->value)
                ->url(UserResource::getUrl('index')),

            Stat::make(
                self::trans('stats.unread_messages'),
                $dashboardService->unreadMessagesCount(),
            )
                ->description(self::trans('stats.unread_messages_description'))
                ->descriptionIcon(Heroicon::OutlinedEnvelope)
                ->color(SemanticColor::Danger->value)
                ->url(ContactMessageResource::getUrl('index')),
        ];
    }

    protected static function translationKeys(): TranslationKey
    {
        return TranslationKey::for(static::class)->forFilament()->setKey('dashboard');
    }
}
