<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\SemanticColor;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UserResource::trans('infolists.sections.profile'))
                ->icon(Heroicon::OutlinedUserCircle)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name')
                        ->label(UserResource::trans('infolists.name'))
                        ->weight(FontWeight::Bold)
                        ->copyable(),

                    IconEntry::make('email_verified_at')
                        ->label(
                            UserResource::trans('infolists.email_verified_at'),
                        )
                        ->boolean(static fn (User $user): bool => $user->isVerified())
                        ->trueColor(SemanticColor::Success->value)
                        ->falseColor(SemanticColor::Danger->value),

                    TextEntry::make('status')
                        ->label(UserResource::trans('infolists.status'))
                        ->badge(),

                    TextEntry::make('created_at')
                        ->label(UserResource::trans('infolists.created_at'))
                        ->since()
                        ->dateTimeTooltip(config('filament.date_time_format'))
                        ->placeholder('-'),
                ]),

            Section::make(UserResource::trans('infolists.sections.contact'))
                ->icon(Heroicon::OutlinedPhone)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('phone.full_phone_number')
                        ->label(UserResource::trans('infolists.phone_number'))
                        ->icon(Heroicon::OutlinedPhone)
                        ->iconColor(SemanticColor::Primary->value)
                        ->copyable()
                        ->placeholder('-'),

                    TextEntry::make('email')
                        ->label(UserResource::trans('infolists.email'))
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->iconColor(SemanticColor::Primary->value)
                        ->copyable(),
                ]),

            Section::make(UserResource::trans('infolists.sections.location'))
                ->icon(Heroicon::OutlinedMapPin)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('addresses.country.name')
                        ->label(UserResource::trans('infolists.country'))
                        ->icon(Heroicon::OutlinedGlobeEuropeAfrica)
                        ->iconColor(SemanticColor::Primary->value)
                        ->placeholder('-'),

                    TextEntry::make('addresses.address')
                        ->label(UserResource::trans('infolists.address'))
                        ->icon(Heroicon::OutlinedMapPin)
                        ->iconColor(SemanticColor::Primary->value)
                        ->copyable()
                        ->placeholder('-'),
                ]),
        ]);
    }
}
