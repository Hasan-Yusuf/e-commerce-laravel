<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    use HasTranslationKeys;

    case SHOP = 'shop';
    case CUSTOMER_MANAGEMENT = 'customer_management';

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }
}
