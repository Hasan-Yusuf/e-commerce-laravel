<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

/**
 * The window the dashboard aggregates read. Sessions carry only the enum's
 * value, so the bounds are derived here rather than stored as dates that would
 * go stale the moment the page is left open overnight.
 */
enum DashboardPeriod: string implements HasLabel
{
    use HasTranslationKeys;

    case TODAY = 'today';
    case SEVEN_DAYS = 'seven_days';
    case THIRTY_DAYS = 'thirty_days';
    case THIS_MONTH = 'this_month';

    public static function default(): self
    {
        return self::THIRTY_DAYS;
    }

    /**
     * Resolves the raw value a widget gets from the dashboard filter form,
     * falling back to the default for a missing or unrecognised option.
     */
    public static function fromFilter(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::default();
    }

    /**
     * The first instant the period includes.
     */
    public function start(): Carbon
    {
        return match ($this) {
            self::TODAY => now()->startOfDay(),
            self::SEVEN_DAYS => now()->subDays(6)->startOfDay(),
            self::THIRTY_DAYS => now()->subDays(29)->startOfDay(),
            self::THIS_MONTH => now()->startOfMonth(),
        };
    }

    /**
     * The last instant the period includes.
     */
    public function end(): Carbon
    {
        return now()->endOfDay();
    }

    #[\Override]
    public function getLabel(): string|Htmlable
    {
        return self::trans($this->value);
    }
}
