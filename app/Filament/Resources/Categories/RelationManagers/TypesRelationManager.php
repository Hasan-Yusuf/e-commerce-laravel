<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Filament\Resources\Types\TypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TypesRelationManager extends RelationManager
{
    protected static string $relationship = 'types';

    protected static ?string $relatedResource = TypeResource::class;

    #[\Override]
    public function table(Table $table): Table
    {
        return $table->headerActions([
            CreateAction::make(),
        ]);
    }
}
