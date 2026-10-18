<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\Tables;

use App\Enums\MimeType;
use App\Filament\Resources\Brands\BrandResource;
use App\Models\Brand;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo_light')
                    ->label(BrandResource::trans('columns.logo_light'))
                    ->conversion(MimeType::IMAGE_WEBP->conversion())
                    ->collection('logo_light')
                    // A logo is drawn at its own width rather than cropped to a
                    // square, so a wordmark stays recognisable; the brand name
                    // names the picture for a reader who cannot see it.
                    ->imageHeight(40)
                    ->alt(static fn (Brand $record): string => $record->name),

                SpatieMediaLibraryImageColumn::make('logo_dark')
                    ->label(BrandResource::trans('columns.logo_dark'))
                    ->conversion(MimeType::IMAGE_WEBP->conversion())
                    ->collection('logo_dark')
                    ->imageHeight(40)
                    ->alt(static fn (Brand $record): string => $record->name),

                TextColumn::make('name')
                    ->label(BrandResource::trans('columns.name'))
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy('name_en', $direction),
                    ),

                TextColumn::make('products_count')
                    ->label(BrandResource::trans('columns.product_count'))
                    ->counts('products'),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([EditAction::make(), DeleteAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(BrandResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                BrandResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice)
            ->emptyStateActions([CreateAction::make()]);
    }
}
