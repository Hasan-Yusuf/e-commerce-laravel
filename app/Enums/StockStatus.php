<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The storefront availability state of a product, derived from its stock,
 * reserved stock, and low-stock threshold.
 */
enum StockStatus: string implements HasColor, HasLabel
{
    use HasTranslationKeys;

    case OUT_OF_STOCK = 'out_of_stock';
    case LOW_STOCK = 'low_stock';
    case IN_STOCK = 'in_stock';

    #[\Override]
    public function getLabel(): string|Htmlable
    {
        return self::trans($this->value);
    }

    #[\Override]
    public function getColor(): string|array|null
    {
        return match ($this) {
            StockStatus::OUT_OF_STOCK => SemanticColor::Danger->value,
            StockStatus::LOW_STOCK => SemanticColor::Warning->value,
            StockStatus::IN_STOCK => SemanticColor::Success->value,
        };
    }
}
