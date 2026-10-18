<?php

declare(strict_types=1);

namespace App\Filament\Resources\Types\Tables;

use App\Enums\SemanticColor;
use App\Filament\Resources\Types\TypeResource;
use App\Models\Type;
use App\Models\TypeSpec;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->modifyQueryUsing(
                static fn (Builder $query): Builder => $query->with('specDefinitions'),
            )
            ->columns([
                TextColumn::make('name')
                    ->label(TypeResource::trans('columns.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name_en')
                    ->label(TypeResource::trans('columns.category'))
                    ->state(static fn (Type $type) => $type->category?->name)
                    ->toggleable()
                    ->searchable(['name_en', 'name_ar'])
                    ->sortable(),

                TextColumn::make('specs')
                    ->label(TypeResource::trans('columns.specs'))
                    ->toggleable()
                    ->searchable(
                        query: static fn (
                            Builder $query,
                            string $search,
                        ): Builder => $query->whereHas(
                            'specDefinitions',
                            static fn (Builder $query): Builder => $query->where('name_en', 'like', "%{$search}%")
                                ->orWhere('name_ar', 'like', "%{$search}%"),
                        ),
                    )
                    ->wrap()
                    ->badge(),

                IconColumn::make('specs_need_review')
                    ->label(TypeResource::trans('columns.specs_need_review'))
                    ->boolean()
                    ->tooltip(
                        static fn (Type $type): ?string => $type->needsSpecReview()
                            ? TypeResource::trans('helpers.specs_need_review')
                            : null,
                    )
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('specs')
                    ->label(TypeResource::trans('filters.specs'))
                    ->options(
                        static fn (): array => TypeSpec::query()
                            ->whereNotNull('name_'.app()->getLocale())
                            ->pluck('name_'.app()->getLocale())
                            ->filter()
                            ->unique()
                            ->mapWithKeys(static fn (string $name): array => [
                                $name => $name,
                            ])
                            ->all(),
                    )
                    ->query(
                        static fn (Builder $query, array $data): Builder => $query->when(
                            $data['value'] ?? null,
                            static fn (
                                Builder $query,
                                string $value,
                            ): Builder => $query->whereHas(
                                'specDefinitions',
                                static fn (Builder $query): Builder => $query->where(
                                    'name_'.app()->getLocale(),
                                    $value,
                                ),
                            ),
                        ),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    // A type whose specs changed under its products carries a
                    // warning until they have been gone through, and this is the
                    // way to them: the page rebuilds every product's values from
                    // the specs and clears the flag.
                    Action::make('updateProducts')
                        ->label(TypeResource::trans('actions.update_products'))
                        ->icon(Heroicon::OutlinedWrenchScrewdriver)
                        ->color(SemanticColor::Warning->value)
                        ->tooltip(TypeResource::trans('actions.update_products_tooltip'))
                        ->visible(
                            static fn (Type $type): bool => $type->needsSpecReview(),
                        )
                        ->url(static fn (Type $type): string => TypeResource::getUrl(
                            'products',
                            ['record' => $type],
                        )),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->groups([
                Group::make('category.name_en')
                    ->label(TypeResource::trans('groups.category'))
                    ->getTitleFromRecordUsing(
                        static fn (Type $type) => (
                            $type->category->name ?? TypeResource::trans('groups.errors.no_category')
                        ),
                    )
                    ->collapsible(),
            ])
            ->emptyStateHeading(TypeResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                TypeResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->emptyStateActions([CreateAction::make()]);
    }
}
