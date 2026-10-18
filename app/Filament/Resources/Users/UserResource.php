<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::UserCircle;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CUSTOMER_MANAGEMENT;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Customer';

    #[\Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone.phone_number'];
    }

    #[\Override]
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var User $record */
        return $record->name;
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var User $record */
        return [
            'Email' => $record->email,
            'Phone' => $record->phone->full_phone_number ?? '',
        ];
    }

    public static function getGlobalSearchResultBadge(Model $user): ?string
    {
        /** @var User $user */
        return $user->status->getLabel();
    }

    public static function getGlobalSearchResultBadgeColor(Model $user): string
    {
        /** @var User $user */
        return $user->status->getColor();
    }

    #[\Override]
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('phone');
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
        return UserForm::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    #[\Override]
    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::make(),
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', '!=', UserRole::ADMIN);
    }
}
