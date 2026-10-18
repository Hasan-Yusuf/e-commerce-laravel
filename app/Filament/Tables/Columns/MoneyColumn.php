<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use App\Support\Money;
use Illuminate\Support\Number;

/**
 * A money column that renders a Money.
 *
 * Filament's own ->money() expects a number and hands the state to
 * Number::currency; a `*_in_fils` column now gives it a Money, which that
 * cannot read. This column takes the amount out of the Money and writes it the
 * same way every other BHD column in the panel is written, so a money column
 * needs no ->state()/->money() pair of its own — and a money of nothing reads
 * as the dash its placeholder gives it, rather than a row of zeroes.
 */
class MoneyColumn extends TextColumn
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(static function (mixed $state): ?string {
            if (blank($state)) {
                return null;
            }

            if ($state instanceof Money) {
                return $state->isZero()
                    ? null
                    : (string) Number::currency($state->toAmount(), 'BHD', (string) config('app.locale'));
            }

            if (! is_numeric($state)) {
                return $state;
            }

            return (string) Number::currency((float) $state, 'BHD', (string) config('app.locale'));
        });
    }
}
