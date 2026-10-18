<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Enums\Unit;
use Closure;
use Filament\Forms\Components\TextInput;

/**
 * A number field that says which unit it is entered in, so no form has to spell
 * out the currency or the percent sign and the side it is written on.
 *
 * The unit is given once, as a case or as a closure that reads it from another
 * field — `->unit(fn (Get $get) => ...)` — and App\Enums\Unit decides whether it
 * leads or trails the number for the locale in hand.
 */
class UnitInput extends TextInput
{
    protected Unit|Closure|null $unit = null;

    public function unit(Unit|Closure|null $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->prefix(fn (): ?string => $this->unitValue()?->prefix());
        $this->suffix(fn (): ?string => $this->unitValue()?->suffix());
    }

    private function unitValue(): ?Unit
    {
        $unit = $this->evaluate($this->unit);

        return $unit instanceof Unit ? $unit : null;
    }
}
