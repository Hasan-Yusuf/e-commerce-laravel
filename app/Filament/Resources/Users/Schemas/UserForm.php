<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var array<string, string> $localeOptions */
        $localeOptions = collect(config('locales'))
            ->map(static fn (array $locale): string => $locale['label'])
            ->all();

        return $schema->components([
            Select::make('status')
                ->label(UserResource::trans('fields.status'))
                ->options(UserStatus::class)
                ->required(),

            Select::make('locale')
                ->label(UserResource::trans('fields.locale'))
                ->options($localeOptions)
                ->required(),
        ]);
    }
}
