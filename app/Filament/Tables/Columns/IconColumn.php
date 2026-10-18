<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use App\Filament\Tables\Columns\Concerns\ToggleableHiddenByDefault;
use Filament\Tables\Columns\IconColumn as BaseIconColumn;

class IconColumn extends BaseIconColumn
{
    use ToggleableHiddenByDefault;
}
