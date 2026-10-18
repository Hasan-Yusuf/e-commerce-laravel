<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Enums\Unit;
use App\Filament\Forms\StateCasts\MoneyStateCast;

/**
 * A money column entered and read as BHD.
 *
 * The field is bound to the `*_in_fils` attribute, so the value a form holds
 * comes back as a Money; this input swaps that for the BHD decimal a person
 * types, and swaps it back on save. That is what lets a money field drop the
 * model's BHD accessor and read the cast instead.
 */
class MoneyInput extends UnitInput
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->type('number')
            ->unit(Unit::CURRENCY)
            ->stateCast(new MoneyStateCast)
            ->rule('numeric');
    }
}
