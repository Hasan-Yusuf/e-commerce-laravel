<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum UserStatus: string implements HasColor, HasIcon, HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case ACTIVE = 'active';
    case IN_ACTIVE = 'in_active';

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }

    #[\Override]
    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => SemanticColor::Success->value,
            self::IN_ACTIVE => SemanticColor::Warning->value,
        };
    }

    #[\Override]
    public function getIcon(): string|\BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::ACTIVE => Heroicon::CheckBadge,
            self::IN_ACTIVE => Heroicon::XCircle,
        };
    }
}
