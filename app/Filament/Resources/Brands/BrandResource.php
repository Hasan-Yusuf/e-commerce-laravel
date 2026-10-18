<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\Schemas\BrandForm;
use App\Filament\Resources\Brands\Tables\BrandsTable;
use App\Models\Brand;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BrandResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::BuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::SHOP;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    #[\Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['name_en', 'name_ar'];
    }

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
        return BrandForm::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
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
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
