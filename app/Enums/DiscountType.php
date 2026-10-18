<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Number;

enum DiscountType: string implements HasColor, HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case PERCENTAGE = 'percentage';
    case FIXED_AMOUNT = 'fixed_amount';

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }

    /**
     * The unit the value is read in: a share of the price as a percentage, an
     * amount off it as money.
     */
    public function unit(): Unit
    {
        return $this->is(self::PERCENTAGE) ? Unit::PERCENT : Unit::CURRENCY;
    }

    /**
     * A value read in the unit its type gives it: a share with the percent
     * sign, an amount as money, written the same way the panel writes every
     * other BHD amount.
     */
    public function formatValue(mixed $value): string
    {
        return $this->is(self::PERCENTAGE)
            ? Unit::PERCENT->format((float) $value)
            : (string) Number::currency((float) $value, 'BHD', (string) config('app.locale'));
    }

    /**
     * The most the type may hold: a share cannot exceed the whole, while an
     * amount off has no ceiling of its own.
     */
    public function maxValue(): ?float
    {
        return match ($this) {
            self::PERCENTAGE => 100.0,
            default => null,
        };
    }

    #[\Override]
    public function getColor(): string
    {
        return SemanticColor::Success->value;
    }
}
