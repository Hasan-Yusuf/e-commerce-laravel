<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Enums\SemanticColor;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_read')
                ->label(ContactMessageResource::trans('actions.mark_read'))
                ->icon(Heroicon::CheckBadge)
                ->color(SemanticColor::Success->value)
                ->tooltip(ContactMessageResource::trans('actions.mark_read_tooltip'))
                ->visible(
                    static fn (ContactMessage $record): bool => ! $record->wasRead(),
                )
                ->action(
                    static fn (ContactMessage $record) => $record->update([
                        'read_at' => now(),
                    ]),
                )
                ->successNotificationTitle(
                    ContactMessageResource::trans(
                        'notifications.marked_read',
                    ),
                ),
        ];
    }
}
