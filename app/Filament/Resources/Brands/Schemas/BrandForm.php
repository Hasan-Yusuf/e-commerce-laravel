<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\Schemas;

use App\Enums\MimeType;
use App\Filament\Resources\Brands\BrandResource;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([...static::getFormSchema()]);
    }

    /**
     * @return array<int, Component>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(BrandResource::trans('sections.main'))
                ->schema([
                    TextInput::make('name_en')
                        ->label(BrandResource::trans('fields.name_en'))
                        ->required()
                        ->unique(),

                    TextInput::make('name_ar')
                        ->label(BrandResource::trans('fields.name_ar'))
                        ->unique(),

                    SpatieMediaLibraryFileUpload::make('logo_light')
                        ->label(BrandResource::trans('fields.logo_light'))
                        ->image()
                        ->helperText(BrandResource::trans('helpers.logo_light'))
                        ->imageEditor()
                        ->conversion(MimeType::IMAGE_WEBP->conversion())
                        ->collection('logo_light'),

                    SpatieMediaLibraryFileUpload::make('logo_dark')
                        ->label(BrandResource::trans('fields.logo_dark'))
                        ->image()
                        ->helperText(BrandResource::trans('helpers.logo_dark'))
                        ->imageEditor()
                        ->conversion(MimeType::IMAGE_WEBP->conversion())
                        ->collection('logo_dark'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }
}
