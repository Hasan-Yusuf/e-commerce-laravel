<?php

declare(strict_types=1);

namespace App\Filament\Forms\StateCasts;

use App\Support\Money;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

/**
 * A Money form field and the BHD decimal the input holds.
 *
 * Filament's own numeric cast runs floatval() over the raw state, which a Money
 * object cannot survive. This cast is the pair that can: the value the form
 * carries in BHD, and a Money on the way back out to the model's AsMoney cast,
 * which is where it becomes whole fils.
 *
 * Only an empty input is nothing: a zero is an amount, so it stays a Money of
 * nothing rather than becoming a null the money columns cannot hold.
 */
class MoneyStateCast implements StateCast
{
    /**
     * The input's value on the way out to the model.
     */
    public function get(mixed $state): ?Money
    {
        if ($state === null || $state === '') {
            return null;
        }

        return $state instanceof Money ? $state : Money::fromBHD((float) $state);
    }

    /**
     * The model's value on the way into the form.
     */
    public function set(mixed $state): ?float
    {
        if ($state === null || $state === '') {
            return null;
        }

        return $state instanceof Money ? $state->toAmount() : (float) $state;
    }
}
