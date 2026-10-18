<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns\Concerns;

use Closure;
use Filament\Tables\Columns\Column;

/**
 * Flips Filament's default so a `->toggleable()` column starts hidden until the
 * administrator turns it on, keeping the initial grid down to the columns that
 * matter. Applied by the panel's own column classes.
 *
 * @phpstan-require-extends Column
 */
trait ToggleableHiddenByDefault
{
    public function toggleable(
        bool|Closure $condition = true,
        bool|Closure $isToggledHiddenByDefault = true,
    ): static {
        return parent::toggleable($condition, $isToggledHiddenByDefault);
    }
}
