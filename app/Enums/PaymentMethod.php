<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasLabel;

/**
 * How an order is paid for. Cash on delivery is the only method in v1; the
 * payment layer (App\Services\Payments) is built so a card or wallet method
 * can be added without touching the checkout flow.
 */
enum PaymentMethod: string implements HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case COD = 'cod';

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
            self::COD => __('checkout.payment.methods.cod.description')
        };
    }
}
