<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Pages\Concerns\HasInfoRelations;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    use HasInfoRelations;

    protected static string $resource = ProductResource::class;

    /**
     * The type the product belongs to, which the infolist names.
     *
     * @return array<int, string>
     */
    protected function infoRelations(): array
    {
        return ['type'];
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
