<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * How an order reaches the shopper: brought to their address, or collected from
 * the store. Neither is charged for yet — the shipping rates that will follow
 * are the hook the delivery step already collects a method for.
 *
 * The method is recorded on the order, so the shop knows how it is to be
 * fulfilled and the shopper reads it back the way they chose it.
 */
enum DeliveryMethod: string implements HasColor, HasIcon, HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case STANDARD = 'standard';
    case PICKUP = 'pickup';

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }

    /**
     * The line under the method's name, wherever the checkout offers it.
     */
    public function description(): string
    {
        return match ($this) {
            self::STANDARD => __(
                'checkout.delivery.methods.standard.description',
            ),
            self::PICKUP => __('checkout.delivery.methods.pickup.description'),
        };
    }

    #[\Override]
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::STANDARD => SemanticColor::Info->value,
            self::PICKUP => SemanticColor::Success->value,
        };
    }

    #[\Override]
    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::STANDARD => Heroicon::OutlinedTruck,
            self::PICKUP => Heroicon::OutlinedBuildingStorefront,
        };
    }
}
