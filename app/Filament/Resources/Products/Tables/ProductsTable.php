<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use App\Enums\SemanticColor;
use App\Enums\StockStatus;
use App\Filament\Actions\SwitchColumnAction;
use App\Filament\Exports\ProductExporter;
use App\Filament\Resources\Products\Actions\RestockProductAction;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Tables\Columns\IconColumn;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Filament\Tables\Columns\TextColumn;
use App\Models\Product;
use App\Services\Catalog\ProductPricingService;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                static fn (Builder $query): Builder => $query->with(['type.category', 'brand'])
                    ->withAvailableStock(),
            )
            ->columns([
                TextColumn::make('name')
                    ->label(ProductResource::trans('columns.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label(ProductResource::trans('columns.sku'))
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                MoneyColumn::make('price_in_fils')
                    ->label(ProductResource::trans('columns.price'))
                    ->searchable()
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            $direction,
                        ) => $query->orderBy('price_in_fils', $direction),
                    ),

                MoneyColumn::make('discount_price')
                    ->label(ProductResource::trans('columns.discount_price'))
                    ->state(static function (Product $record): string|float {
                        $pricingService = app(ProductPricingService::class);
                        $priceInFils = $pricingService->getCurrentPrice($record);

                        // The storefront's own pricing decides this column, so
                        // the panel cannot disagree with what a shopper pays.
                        if ($priceInFils >= $record->price_in_fils->toFils()) {
                            return '-';
                        }

                        return Money::fromFils($priceInFils)->toAmount();
                    })
                    ->toggleable(),

                MoneyColumn::make('cost_price_in_fils')
                    ->label(ProductResource::trans('columns.cost_price'))
                    ->toggleable()
                    ->searchable()
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy('cost_price_in_fils', $direction),
                    ),

                TextColumn::make('stock')
                    ->label(ProductResource::trans('columns.stock'))
                    ->sortable()
                    ->numeric(),

                TextColumn::make('reserved_stock')
                    ->label(ProductResource::trans('columns.reserved_stock'))
                    ->sortable()
                    ->numeric()
                    ->default(0)
                    ->toggleable(),

                TextColumn::make('available_stock')
                    ->label(ProductResource::trans('columns.available_stock'))
                    ->sortable()
                    ->numeric()
                    ->toggleable(),

                TextColumn::make('stock_status')
                    ->label(ProductResource::trans('columns.stock_status'))
                    ->state(static fn (Product $record): StockStatus => $record->stockStatus())
                    ->badge(),

                TextColumn::make('type.name')
                    ->label(ProductResource::trans('columns.type_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type.category.name_en')
                    ->label(ProductResource::trans('columns.category_name'))
                    ->state(
                        static fn (Product $product) => $product->type?->category?->name,
                    )
                    ->toggleable()
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(),

                IconColumn::make('featured_at')
                    ->label(ProductResource::trans('columns.featured_at'))
                    ->toggleable()
                    ->boolean(static fn (Product $product) => $product->isFeatured()),

                IconColumn::make('activated_at')
                    ->label(ProductResource::trans('columns.activated_at'))
                    ->toggleable()
                    ->boolean(static fn (Product $product) => $product->isActive()),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('type')
                    ->relationship('type', 'name')
                    ->label(ProductResource::trans('filters.type_name'))
                    ->preload(),

                SelectFilter::make('brand')
                    ->relationship('brand', 'name_en')
                    ->label(ProductResource::trans('filters.brand_name'))
                    ->preload(),

                SelectFilter::make('stock_status')
                    ->label(ProductResource::trans('filters.stock_status'))
                    ->options(StockStatus::class)
                    ->query(static fn (Builder $query, array $data): Builder => match (StockStatus::tryFrom(
                        (string) ($data['value'] ?? ''),
                    )) {
                        StockStatus::OUT_OF_STOCK => $query->outOfStock(),
                        StockStatus::LOW_STOCK => $query->lowStock(),
                        StockStatus::IN_STOCK => $query->inStock(),
                        default => $query,
                    }),
            ])
            ->headerActions([
                ExportAction::make()->exporter(ProductExporter::class),
            ])
            ->recordActions([
                RestockProductAction::make(),

                Action::make('duplicate')
                    ->label(ProductResource::trans('actions.duplicate'))
                    ->icon(Heroicon::DocumentDuplicate)
                    ->tooltip(ProductResource::trans('actions.duplicate_tooltip'))
                    ->action(
                        static fn (Product $record) => redirect(
                            ProductResource::getUrl('create', [
                                'copy_id' => $record->id,
                            ]),
                        ),
                    ),

                ActionGroup::make([
                    SwitchColumnAction::make('activated_at')
                        ->onLabel(ProductResource::trans('actions.activate'))
                        ->offLabel(ProductResource::trans('actions.deactivate'))
                        ->onTooltip(ProductResource::trans('actions.activate_tooltip'))
                        ->offTooltip(ProductResource::trans('actions.deactivate_tooltip'))
                        ->onIcon(Heroicon::Power)
                        ->offIcon(Heroicon::Power)
                        ->onColor(SemanticColor::Success->value)
                        ->offColor(SemanticColor::Danger->value)
                        ->onNotification(
                            ProductResource::trans('notifications.activated'),
                        )
                        ->offNotification(
                            ProductResource::trans('notifications.deactivated'),
                        )
                        ->requiresConfirmation(),

                    SwitchColumnAction::make('featured_at')
                        ->onLabel(ProductResource::trans('actions.feature'))
                        ->offLabel(ProductResource::trans('actions.unfeature'))
                        ->onTooltip(ProductResource::trans('actions.feature_tooltip'))
                        ->offTooltip(ProductResource::trans('actions.unfeature_tooltip'))
                        ->onIcon(Heroicon::Star)
                        ->offIcon(Heroicon::Star)
                        ->onColor(SemanticColor::Warning->value)
                        ->offColor(SemanticColor::Gray->value)
                        ->onNotification(
                            ProductResource::trans('notifications.featured'),
                        )
                        ->offNotification(
                            ProductResource::trans('notifications.unfeatured'),
                        )
                        ->requiresConfirmation(),

                    EditAction::make(),

                    ViewAction::make(),
                    RestoreAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...self::getBulkActions(),

                    ExportBulkAction::make()->exporter(ProductExporter::class),

                    DeleteBulkAction::make(),

                    ForceDeleteBulkAction::make(),

                    RestoreBulkAction::make(),
                ]),
            ])
            ->groups([
                Group::make('type.name')
                    ->label(ProductResource::trans('groups.type'))
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        static fn (Product $product) => (
                            $product->type->name ?? ProductResource::trans('groups.errors.no_type')
                        ),
                    ),

                Group::make('brand.name_en')
                    ->label(ProductResource::trans('groups.brand'))
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        static fn (Product $product) => (
                            $product->brand->name ?? ProductResource::trans('groups.errors.no_brand')
                        ),
                    ),

                Group::make('type.category.name_en')
                    ->label(ProductResource::trans('groups.category'))
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        static fn (Product $product) => (
                            $product->type->category->name ?? ProductResource::trans('groups.error.no_category')
                        ),
                    ),
            ])
            ->emptyStateHeading(ProductResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                ProductResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedSquares2x2)
            ->emptyStateActions([CreateAction::make()]);
    }

    /**
     * Catalogue flags an administrator flips across many SKUs at once — a
     * seasonal sale is the routine these exist for, and opening the product
     * wizard once per SKU is too heavy for it.
     *
     * @return array<int, BulkAction>
     */
    private static function getBulkActions(): array
    {
        return [
            BulkAction::make('activate')
                ->label(ProductResource::trans('actions.activate'))
                ->tooltip(ProductResource::trans('actions.activate_selected_tooltip'))
                ->icon(Heroicon::Power)
                ->color(SemanticColor::Success->value)
                ->requiresConfirmation()
                ->action(static function (Collection $records): void {
                    $records->each(
                        static fn (Product $product) => $product->update([
                            'activated_at' => now(),
                        ]),
                    );
                })
                ->deselectRecordsAfterCompletion()
                ->successNotificationTitle(
                    ProductResource::trans('notifications.activated'),
                ),

            BulkAction::make('deactivate')
                ->label(ProductResource::trans('actions.deactivate'))
                ->tooltip(ProductResource::trans('actions.deactivate_selected_tooltip'))
                ->icon(Heroicon::Power)
                ->color(SemanticColor::Danger->value)
                ->requiresConfirmation()
                ->action(static function (Collection $records): void {
                    $records->each(
                        static fn (Product $product) => $product->update([
                            'activated_at' => null,
                        ]),
                    );
                })
                ->deselectRecordsAfterCompletion()
                ->successNotificationTitle(
                    ProductResource::trans('notifications.deactivated'),
                ),

            BulkAction::make('feature')
                ->label(ProductResource::trans('actions.feature'))
                ->tooltip(ProductResource::trans('actions.feature_selected_tooltip'))
                ->icon(Heroicon::Star)
                ->color(SemanticColor::Warning->value)
                ->requiresConfirmation()
                ->action(static function (Collection $records): void {
                    $records->each(
                        static fn (Product $product) => $product->update([
                            'featured_at' => now(),
                        ]),
                    );
                })
                ->deselectRecordsAfterCompletion()
                ->successNotificationTitle(
                    ProductResource::trans('notifications.featured'),
                ),

            BulkAction::make('unfeature')
                ->label(ProductResource::trans('actions.unfeature'))
                ->tooltip(ProductResource::trans('actions.unfeature_selected_tooltip'))
                ->icon(Heroicon::Star)
                ->color(SemanticColor::Gray->value)
                ->requiresConfirmation()
                ->action(static function (Collection $records): void {
                    $records->each(
                        static fn (Product $product) => $product->update([
                            'featured_at' => null,
                        ]),
                    );
                })
                ->deselectRecordsAfterCompletion()
                ->successNotificationTitle(
                    ProductResource::trans('notifications.unfeatured'),
                ),
        ];
    }
}
