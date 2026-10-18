<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\HasTranslationKeys;
use Filament\Support\Contracts\HasLabel;

enum ToastType: string implements HasLabel
{
    use HasTranslationKeys;

    case SUCCESS = 'success';
    case WARNING = 'warning';
    case DANGER = 'danger';
    case INFO = 'info';

    /**
     * The type a message that named none reads as. An enum carries no state, so
     * this is a constant rather than something set: a message the application did
     * not label is an announcement rather than a result — the page that did
     * something says so about itself — so it is information.
     */
    public const self DEFAULT = self::INFO;

    public static function getDefault(): self
    {
        return self::DEFAULT;
    }

    /**
     * The type a stored or dispatched value names, or the default the caller
     * gives — and the class's own when it gives none. What arrives from outside,
     * a flash or the `notify` event, is not to be trusted to be one of these
     * names, and a type that arrives as itself is taken as itself; anything else
     * reads as the default rather than taking the page down.
     */
    public static function fromValue(mixed $value, ?self $default = null): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $default ??= self::getDefault();

        return is_string($value)
            ? self::tryFrom($value) ?? $default
            : $default;
    }

    #[\Override]
    public function getLabel(): string
    {
        return self::trans($this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::SUCCESS => 'icons.success-icon',
            self::WARNING => 'icons.warning-icon',
            self::DANGER => 'icons.danger-icon',
            self::INFO => 'icons.info-icon',
        };
    }
}
