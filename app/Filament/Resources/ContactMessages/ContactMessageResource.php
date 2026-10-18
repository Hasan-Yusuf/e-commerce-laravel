<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages;

use App\Enums\CacheKeys;
use App\Enums\NavigationGroup;
use App\Enums\SemanticColor;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\ContactMessages\Schemas\ContactMessageInfolist;
use App\Filament\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use App\Support\HasTranslationKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class ContactMessageResource extends Resource
{
    use HasTranslationKeys;

    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Envelope;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CUSTOMER_MANAGEMENT;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'subject';

    #[\Override]
    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember(
            CacheKeys::UnreadMessagesCount,
            now()->addMinutes(10),
            static fn (): int => ContactMessage::query()->whereNull('read_at')->count(),
        );

        return $count > 0 ? (string) $count : null;
    }

    #[\Override]
    public static function getNavigationBadgeColor(): ?string
    {
        return SemanticColor::Danger->value;
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
    public static function infolist(Schema $schema): Schema
    {
        return ContactMessageInfolist::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
