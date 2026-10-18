<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Enums\SemanticColor;
use App\Filament\Actions\SwitchColumnAction;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Tables\Columns\IconColumn;
use App\Filament\Tables\Columns\TextColumn;
use App\Models\ContactMessage;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(ContactMessageResource::trans('columns.name'))
                    ->searchable(),

                TextColumn::make('email')
                    ->label(ContactMessageResource::trans('columns.email'))
                    ->searchable(),

                TextColumn::make('subject')
                    ->label(ContactMessageResource::trans('columns.subject'))
                    ->searchable()
                    ->limit(40),

                IconColumn::make('read_at')
                    ->label(ContactMessageResource::trans('columns.read_at'))
                    ->boolean(
                        static fn (ContactMessage $message): bool => $message->wasRead(),
                    )
                    ->trueIcon(Heroicon::CheckBadge)
                    ->falseIcon(Heroicon::Envelope)
                    ->trueColor(SemanticColor::Success->value)
                    ->falseColor(SemanticColor::Warning->value),

                TextColumn::make('created_at')
                    ->label(ContactMessageResource::trans('columns.created_at'))
                    ->since()
                    ->dateTimeTooltip(config('filament.date_time_format'))
                    ->sortable(),
            ])
            ->filters([
                Filter::make('unread')
                    ->label(ContactMessageResource::trans('filters.unread'))
                    ->query(
                        static fn (Builder $query): Builder => $query->whereNull(
                            'read_at',
                        ),
                    ),
            ])
            ->recordActions([
                // Reading a message is a one-way switch, so the action that
                // switches it back off is never offered.
                SwitchColumnAction::make('read_at')
                    ->onLabel(ContactMessageResource::trans('actions.mark_read'))
                    ->onTooltip(ContactMessageResource::trans('actions.mark_read_tooltip'))
                    ->onIcon(Heroicon::CheckBadge)
                    ->onColor(SemanticColor::Success->value)
                    ->visible(
                        static fn (ContactMessage $message): bool => ! $message->wasRead(),
                    )
                    ->onNotification(
                        ContactMessageResource::trans('notifications.marked_read'),
                    ),

                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(
                ContactMessageResource::trans('empty_state.heading'),
            )
            ->emptyStateDescription(
                ContactMessageResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedEnvelope);
    }
}
