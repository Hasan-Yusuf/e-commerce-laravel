<?php

declare(strict_types=1);

namespace App\Filament\Resources\Discounts\Pages;

use App\Filament\Resources\Discounts\DiscountResource;
use App\Filament\Resources\Pages\Concerns\HasInfoRelations;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDiscount extends ViewRecord
{
    use HasInfoRelations;

    protected static string $resource = DiscountResource::class;

    /**
     * Everything the discount is pinned to, which the infolist lists.
     *
     * @return array<int, string>
     */
    protected function infoRelations(): array
    {
        return ['products', 'productTypes', 'categories', 'brands'];
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
