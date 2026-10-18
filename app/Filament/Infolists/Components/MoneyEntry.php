<?php

declare(strict_types=1);

namespace App\Filament\Infolists\Components;

use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Number;

/**
 * An infolist entry that renders a Money.
 *
 * The infolist twin of App\Filament\Tables\Columns\MoneyColumn: the same
 * reading of a `*_in_fils` column, so an order reads the same amount in its
 * table row and on its view page. A money of nothing reads as the dash its
 * placeholder gives it, rather than a row of zeroes.
 */
class MoneyEntry extends TextEntry
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
