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

enum ProductStatus: string implements HasColor, HasIcon, HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case NOT_AVAILABLE = 'not_available';
    case AVAILABLE = 'available';

    #[\Override]
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NOT_AVAILABLE => SemanticColor::Danger->value,
            self::AVAILABLE => SemanticColor::Success->value,
        };
    }

    #[\Override]
    public function getLabel(): string|Htmlable|null
    {
        return self::trans($this->value);
    }

    #[\Override]
    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::NOT_AVAILABLE => Heroicon::ExclamationTriangle,
            self::AVAILABLE => Heroicon::Check,
        };
    }
}
