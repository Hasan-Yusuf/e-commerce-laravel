<?php

declare(strict_types=1);

namespace App\Filament\Resources\Countries;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Filament\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CountryResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAmericas;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::GlobeAmericas;

    protected static ?int $navigationSort = 4;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CUSTOMER_MANAGEMENT;

    #[\Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['name_en', 'name_ar'];
    }

    #[\Override]
    public static function getGlobalSearchResultTitle(?Model $record): string
    {
        /** @var Country $record */
        return $record->name;
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
        return CountryForm::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
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
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
