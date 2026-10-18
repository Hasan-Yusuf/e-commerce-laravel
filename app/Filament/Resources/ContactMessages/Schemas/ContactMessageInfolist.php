<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Enums\SemanticColor;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(ContactMessageResource::trans('sections.sender'))
                ->icon(Heroicon::Envelope)
                ->iconColor(SemanticColor::Primary->value)
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label(
                        ContactMessageResource::trans('infolists.name'),
                    ),

                    TextEntry::make('email')
                        ->label(
                            ContactMessageResource::trans('infolists.email'),
                        )
                        ->copyable(),

                    TextEntry::make('subject')->label(
                        ContactMessageResource::trans('infolists.subject'),
                    ),

                    TextEntry::make('read_at')
                        ->label(
                            ContactMessageResource::trans('infolists.read_at'),
                        )
                        ->since()
                        ->dateTimeTooltip('h:iA d-m-Y')
                        ->placeholder('-'),
                ]),

            Section::make(ContactMessageResource::trans('sections.message'))
                ->icon(Heroicon::ChatBubbleLeft)
                ->iconColor(SemanticColor::Primary->value)
                ->schema([
                    TextEntry::make('message')
                        ->label(
                            ContactMessageResource::trans('infolists.message'),
                        )
                        ->markdown()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
