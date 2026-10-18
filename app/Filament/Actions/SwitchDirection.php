<?php

declare(strict_types=1);

namespace App\Filament\Actions;

/**
 * The direction a switch column action moves a record in, so resolution reads
 * which way it is going rather than a flag's truthiness.
 */
enum SwitchDirection
{
    case On;

    case Off;

    public function isOn(): bool
    {
        return $this === self::On;
    }

    public function pick(ColumnSwitchSide $on, ColumnSwitchSide $off): ColumnSwitchSide
    {
        return $this === self::On ? $on : $off;
    }
}
