<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserRole: string implements HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case ADMIN = 'admin';
    case CUSTOMER = 'customer';

    #[\Override]
    public function getLabel(): string|Htmlable|null
    {
        return self::trans($this->value);
    }
}
