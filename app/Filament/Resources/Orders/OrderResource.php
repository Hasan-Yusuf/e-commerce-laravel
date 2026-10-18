<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders;

use App\Enums\NavigationGroup;
use App\Enums\SemanticColor;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Services\DashboardService;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class OrderResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CUSTOMER_MANAGEMENT;

    protected static ?int $navigationSort = 1;

    /**
     * The same to-fulfil figure the dashboard shows, read from the service that
     * owns it so the badge and the dashboard can never disagree.
     */
    #[\Override]
    public static function getNavigationBadge(): ?string
    {
        $count = app(DashboardService::class)->ordersToFulfilCount();

        return $count > 0 ? (string) $count : null;
    }

    #[\Override]
    public static function getNavigationBadgeColor(): ?string
    {
        return SemanticColor::Warning->value;
    }

    #[\Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['number'];
    }

    #[\Override]
    public static function getGlobalSearchResultTitle(?Model $record): string
    {
        /** @var Order $record */
        return $record->number;
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public static function getGlobalSearchResultDetails(?Model $record): array
    {
        /** @var Order $record */
        return [
            'Customer' => (string) $record->user?->name,
            'Status' => $record->status->getLabel(),
        ];
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
    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    #[\Override]
    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    #[\Override]
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes(
                [SoftDeletingScope::class],
            );
    }
}
