<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The unit a number is entered in.
 *
 * A unit is written where the reader expects it: after the number in a
 * left-to-right locale and before it in a right-to-left one, which is why a
 * field asks for its unit once and not for a prefix and a suffix. The
 * directions are the ones the application already declares for its locales
 * (see config/locales.php).
 */
enum Unit: string
{
    case CURRENCY = 'currency';
    case PERCENT = 'percent';
    case WEIGHT = 'weight';

    /**
     * The unit as it is written in the current locale.
     */
    public function label(): string
    {
        return match ($this) {
            self::CURRENCY => __('units.currency'),
            self::PERCENT => '%',
            self::WEIGHT => __('units.weight'),
        };
    }

    /**
     * The decimals the unit's numbers are written with: money to the fil, a
     * measured number to two places.
     */
    public function precision(): int
    {
        return match ($this) {
            self::CURRENCY => 3,
            self::PERCENT, self::WEIGHT => 2,
        };
    }

    /**
     * A number written with the unit it is read in, on the side the locale reads
     * it — "10%", or "BHD 5.000" where the unit leads.
     */
    public function format(string|int|float $value): string
    {
        $number = number_format((float) $value, $this->precision());

        return ($this->prefix() ?? '').$number.($this->suffix() ?? '');
    }

    /**
     * The unit written after the number, which is where a left-to-right locale
     * reads it — and nothing where the locale leads with it instead.
     */
    public function suffix(): ?string
    {
        return $this->leads() ? null : $this->label();
    }

    /**
     * The unit written before the number, which is where a right-to-left locale
     * reads it.
     */
    public function prefix(): ?string
    {
        return $this->leads() ? $this->label() : null;
    }

    private function leads(): bool
    {
        return config('locales.'.app()->getLocale().'.dir') === 'rtl';
    }
}
