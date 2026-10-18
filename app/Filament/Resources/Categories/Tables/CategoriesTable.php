<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Tables;

use App\Enums\MimeType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('Thumbnail')
                    ->label(
                        CategoryResource::trans('columns.thumbnail'),
                    )
                    ->conversion(MimeType::IMAGE_WEBP->conversion()),

                TextColumn::make('name')
                    ->label(CategoryResource::trans('columns.name'))
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(
                        query: static fn (
                            Builder $query,
                            string $direction,
                        ) => $query->orderBy('name_en', $direction),
                    ),

                TextColumn::make('parent.name')
                    ->label(CategoryResource::trans('columns.parent'))
                    ->emptyTooltip('No Main Category')
                    ->toggleable(),

                TextColumn::make('types.name')
                    ->label(CategoryResource::trans('columns.types'))
                    ->toggleable()
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label(CategoryResource::trans('filters.parent_name'))
                    ->options(
                        static fn () => Category::query()
                            ->whereHas('children')
                            ->get()
                            ->pluck('name', 'id'),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([EditAction::make(), DeleteAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->groups([
                Group::make('parent.name_en')
                    ->label(CategoryResource::trans('groups.parent'))
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        static fn (Category $category) => (
                            $category->parent->name ?? CategoryResource::trans('groups.errors.no_parent')
                        ),
                    ),
            ])
            ->emptyStateHeading(
                CategoryResource::trans('empty_state.heading'),
            )
            ->emptyStateDescription(
                CategoryResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedRectangleGroup)
            ->emptyStateActions([CreateAction::make()]);
    }
}
