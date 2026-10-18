<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Enums\NavigationGroup;
use App\Enums\SemanticColor;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\RelationManagers\DiscountRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use App\Services\DashboardService;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;
use UnitEnum;

class ProductResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Squares2x2;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::SHOP;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku'];
    }

    #[Override]
    public static function getGlobalSearchResultTitle(
        ?Model $record,
    ): string|Htmlable {
        /** @var Product $record */
        return $record->name;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public static function getGlobalSearchResultDetails(?Model $record): array
    {
        /** @var Product $record */
        return [
            self::trans('fields.sku') => $record->sku,
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return self::trans('labels.model_name');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return self::trans('labels.plural_model_name');
    }

    /**
     * The same low-stock figure the dashboard shows, read from the service that
     * owns it so the badge and the dashboard can never disagree.
     */
    #[Override]
    public static function getNavigationBadge(): ?string
    {
        $count = app(DashboardService::class)->lowStockProductsCount();

        return $count > 0 ? (string) $count : null;
    }

    #[Override]
    public static function getNavigationBadgeColor(): ?string
    {
        return SemanticColor::Warning->value;
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [DiscountRelationManager::make()];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes(
                [SoftDeletingScope::class],
            );
    }
}
