<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use App\Filament\Tables\Columns\Concerns\ToggleableHiddenByDefault;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;

class TextColumn extends BaseTextColumn
{
    use ToggleableHiddenByDefault;
}
