<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasLabel;

enum AddressType: string implements HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case SHIPPING = 'shipping';
    case BILLING = 'billing';

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }
}
