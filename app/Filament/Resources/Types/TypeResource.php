<?php

declare(strict_types=1);

namespace App\Filament\Resources\Types;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Types\Pages\CreateType;
use App\Filament\Resources\Types\Pages\EditType;
use App\Filament\Resources\Types\Pages\ListTypes;
use App\Filament\Resources\Types\Pages\UpdateTypeProducts;
use App\Filament\Resources\Types\Schemas\TypeForm;
use App\Filament\Resources\Types\Tables\TypesTable;
use App\Models\Type;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TypeResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = Type::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::RectangleStack;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::SHOP;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    #[\Override]
    public static function getModelLabel(): string
    {
        return self::trans('labels.model_name');
    }

    #[\Override]
    public static function getPluralModelLabel(): string
    {
        return self::trans('labels.plural_model_name');
    }

    #[\Override]
    public static function getNavigationLabel(): string
    {
        return self::trans('labels.navigation');
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return TypeForm::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return TypesTable::configure($table);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTypes::route('/'),
            'create' => CreateType::route('/create'),
            'edit' => EditType::route('/{record}/edit'),
            'products' => UpdateTypeProducts::route('/{record}/products'),
        ];
    }
}
