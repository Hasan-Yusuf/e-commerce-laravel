<?php

declare(strict_types=1);

namespace App\Filament\Resources\Banners;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Filament\Resources\Banners\Schemas\BannerForm;
use App\Filament\Resources\Banners\Tables\BannersTable;
use App\Models\Banner;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BannerResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = Banner::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Photo;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::SHOP;

    protected static ?int $navigationSort = 4;

    /**
     * The title is read in the panel's own language, so the search has to run
     * over both languages the banner keeps a title in rather than a column the
     * title no longer has.
     */
    #[\Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['title_en', 'title_ar'];
    }

    #[\Override]
    public static function getModelLabel(): string
    {
        return self::trans('labels.model_name');
    }

    #[\Override]
    public static function getPluralModelLabel(): string
    {
        return self::trans('labels.plural_model_name');
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return BannerForm::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return BannersTable::configure($table);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
