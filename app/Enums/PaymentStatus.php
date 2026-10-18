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
 * Where a payment stands. A cash-on-delivery payment is created pending and is
 * settled (paid) when the courier collects the money; refunded and failed are
 * the terminal states a future gateway reports.
 */
enum PaymentStatus: string implements HasColor, HasIcon, HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }

    #[\Override]
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => SemanticColor::Warning->value,
            self::PAID => SemanticColor::Success->value,
            self::FAILED => SemanticColor::Danger->value,
            self::REFUNDED => SemanticColor::Danger->value,
        };
    }

    #[\Override]
    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::PENDING => Heroicon::OutlinedClock,
            self::PAID => Heroicon::Check,
            self::FAILED => Heroicon::XMark,
            self::REFUNDED => Heroicon::OutlinedReceiptRefund,
        };
    }
}
