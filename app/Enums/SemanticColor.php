<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\CompareableEnum;
use Filament\Support\Contracts\HasColor;

enum SemanticColor: string implements HasColor
{
    use CompareableEnum;

    case Primary = 'primary';
    case Gray = 'gray';
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';

    #[\Override]
    public function getColor(): string
    {
        return $this->value;
    }
}
