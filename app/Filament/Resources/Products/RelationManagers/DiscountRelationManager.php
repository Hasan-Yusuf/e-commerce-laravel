<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\DiscountType;
use App\Filament\Resources\Discounts\DiscountResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscountRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    #[\Override]
    public static function getModelLabel(): string
    {
        return DiscountResource::trans('labels.model_name');
    }

    #[\Override]
    public static function getPluralModelLabel(): string
    {
        return DiscountResource::trans('labels.plural_model_name');
    }

    #[\Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(
                DiscountResource::trans('fields.name'),
            ),

            Select::make('type')
                ->label(DiscountResource::trans('fields.type'))
                ->options(DiscountType::class)
                ->default(DiscountType::FIXED_AMOUNT)
                ->required(),

            TextInput::make('value')
                ->label(DiscountResource::trans('fields.value'))
                ->required()
                ->numeric(),

            DateTimePicker::make('starts_at')->label(
                DiscountResource::trans('fields.starts_at'),
            ),

            DateTimePicker::make('ends_at')->label(
                DiscountResource::trans('fields.ends_at'),
            ),

            DateTimePicker::make('activated_at')->label(
                DiscountResource::trans('fields.activated_at'),
            ),
        ]);
    }

    #[\Override]
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),

                TextColumn::make('type')->badge()->searchable(),

                TextColumn::make('value')->numeric()->sortable(),

                TextColumn::make('starts_at')->dateTime()->sortable(),

                TextColumn::make('ends_at')->dateTime()->sortable(),

                TextColumn::make('activated_at')->dateTime()->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([CreateAction::make(), AttachAction::make()])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
