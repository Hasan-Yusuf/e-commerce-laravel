<?php

declare(strict_types=1);

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\MimeType;
use App\Filament\Resources\Banners\BannerResource;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BannerForm
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
            Section::make([
                SpatieMediaLibraryFileUpload::make('image')
                    ->label(BannerResource::trans('fields.image'))
                    ->image()
                    ->helperText(BannerResource::trans('helpers.image'))
                    ->collection('image')
                    ->conversion(MimeType::IMAGE_WEBP->conversion())
                    ->columnSpanFull(),

                TextInput::make('title_en')
                    ->label(BannerResource::trans('fields.title_en'))
                    ->placeholder(BannerResource::trans('placeholders.title_en'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(static function (Set $set, mixed $state): void {
                        $set('link', static::linkFor($state));
                    }),

                TextInput::make('title_ar')
                    ->label(BannerResource::trans('fields.title_ar'))
                    ->placeholder(BannerResource::trans('placeholders.title_ar')),

                TextInput::make('link')
                    ->label(BannerResource::trans('fields.link'))
                    ->placeholder(BannerResource::trans('placeholders.link'))
                    ->helperText(BannerResource::trans('helpers.link'))
                    ->required(),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * The address a banner opens: the products index, searched for the banner's
     * own English name, so the products gathered under the offer are what the
     * shopper sees. It is written from the English title because the shop's
     * search runs over the catalog's English names.
     */
    private static function linkFor(mixed $title): string
    {
        return route('products.index', [
            'q' => Str::slug(is_scalar($title) ? (string) $title : ''),
        ]);
    }
}
