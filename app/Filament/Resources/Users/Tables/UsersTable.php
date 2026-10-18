<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Tables\Columns\IconColumn;
use App\Filament\Tables\Columns\TextColumn;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(
                    UserResource::trans('columns.name'),
                ),

                TextColumn::make('email')->label(
                    UserResource::trans('columns.email'),
                ),

                TextColumn::make('status')
                    ->label(UserResource::trans('columns.status'))
                    ->iconColor(UserStatus::class),

                TextColumn::make('orders_count')
                    ->label(UserResource::trans('columns.order_count'))
                    ->counts('orders')
                    ->alignCenter(),

                TextColumn::make('phone.phone_number')->label(
                    UserResource::trans('columns.phone_number'),
                ),

                IconColumn::make('email_verified_at')
                    ->label(UserResource::trans('columns.verified'))
                    ->boolean(static fn (User $user) => $user->isVerified()),

                TextColumn::make('addresses.country.name')
                    ->label(UserResource::trans('columns.address'))
                    ->listWithLineBreaks(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(UserResource::trans('filters.status'))
                    ->options(UserStatus::class),

                Filter::make('has_orders')
                    ->label(UserResource::trans('filters.has_orders'))
                    ->query(
                        static fn (Builder $query): Builder => $query->has('orders'),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(UserResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                UserResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedUserCircle);
    }
}
