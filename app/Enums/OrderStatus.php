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

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    use CompareableEnum, HasTranslationKeys;

    case PENDING = 'pending';
    case PROCESSING = 'processing';

    case DELIVERED = 'delivered';
    case SHIPPED = 'shipped';

    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public static function getDefaultStatus(): self
    {
        return self::PENDING;
    }

    /**
     * The moves an order may legally make, keyed by where it is now.
     *
     * This is the only place a legal transition is defined: the panel's
     * actions decide whether to offer themselves from it, and every pipeline
     * starts by asking it, so the two can never drift.
     *
     * @var array<string, array<int, self>>
     */
    private const array LEGAL_TRANSITIONS = [
        self::PENDING->value => [
            self::PROCESSING,
            self::SHIPPED,
            self::DELIVERED,
            self::CANCELLED,
        ],
        self::PROCESSING->value => [
            self::SHIPPED,
            self::DELIVERED,
            self::CANCELLED,
        ],
        self::SHIPPED->value => [self::REFUNDED],
        self::DELIVERED->value => [self::REFUNDED],

        // An order that was called off or refunded is only ever taken back up
        // as a pending one: that is the move the panel's reinstate offers, and
        // keeping it to the one target is what keeps confirming — which targets
        // processing — from being offered on an order that has been called off.
        self::CANCELLED->value => [self::PENDING],
        self::REFUNDED->value => [self::PENDING],
    ];

    /**
     * Whether an order may move from this status to the given one.
     */
    public function canTransitionTo(self $to): bool
    {
        return in_array(
            $to,
            self::LEGAL_TRANSITIONS[$this->value] ?? [],
            true,
        );
    }

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }

    #[\Override]
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => SemanticColor::Info->value,
            self::PROCESSING => SemanticColor::Warning->value,
            self::DELIVERED => SemanticColor::Success->value,
            self::SHIPPED => SemanticColor::Success->value,
            self::REFUNDED => SemanticColor::Danger->value,
            self::CANCELLED => SemanticColor::Danger->value,
        };
    }

    #[\Override]
    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::PENDING => Heroicon::OutlinedClock,
            self::PROCESSING => Heroicon::ArrowPathRoundedSquare,
            self::DELIVERED, self::SHIPPED => Heroicon::Check,
            self::REFUNDED => Heroicon::OutlinedReceiptRefund,
            self::CANCELLED => Heroicon::XMark,
        };
    }
}
