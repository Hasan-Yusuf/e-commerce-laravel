<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CategoryResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::RectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::SHOP;

    protected static ?int $navigationSort = 2;

    #[\Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['name_en', 'name_ar'];
    }

    #[\Override]
    public static function getGlobalSearchResultTitle(
        Model $record,
    ): string|Htmlable {
        /** @var Category $record */
        return $record->name;
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
        return CategoryForm::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
