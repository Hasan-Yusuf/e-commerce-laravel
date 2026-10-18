<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\RelationManagers;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $relatedResource = ProductResource::class;

    #[\Override]
    public function table(Table $table): Table
    {
        return $table->headerActions([CreateAction::make()]);
    }
}
