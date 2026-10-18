<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Pages\Concerns\HasInfoRelations;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use HasInfoRelations;

    protected static string $resource = UserResource::class;

    /**
     * The shopper's number and its country, and the addresses on file with
     * theirs.
     *
     * @return array<int, string>
     */
    protected function infoRelations(): array
    {
        return [
            'phone.country',
            'addresses.country',
        ];
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
