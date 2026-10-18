<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\DashboardService;
use App\Support\HasTranslationKeys;
use App\Support\Money;
use App\Support\TranslationKey;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The revenue trend over the last thirty days. One grouped query feeds the whole
 * series (DashboardService::revenuePerDay fills the empty days in PHP), and
 * polling is off so the chart is not re-queried every five seconds.
 */
class RevenueChart extends ChartWidget
{
    use HasTranslationKeys;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public function getHeading(): string|Htmlable|null
    {
        return self::trans('chart.heading');
    }

    public function getDescription(): string|Htmlable|null
    {
        return self::trans('chart.description');
    }

    #[\Override]
    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $series = app(DashboardService::class)->revenuePerDay();

        return [
            'datasets' => [
                [
                    'label' => self::trans('chart.dataset'),
                    'data' => array_map(
                        static fn (array $day): float => Money::fromFils((int) $day['total_in_fils'])->toAmount(),
                        $series,
                    ),
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(
                static fn (array $day): string => $day['label'],
                $series,
            ),
        ];
    }

    #[\Override]
    protected function getOptions(): array|RawJs|null
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected static function translationKeys(): TranslationKey
    {
        return TranslationKey::for(static::class)->forFilament()->setKey('dashboard');
    }
}
