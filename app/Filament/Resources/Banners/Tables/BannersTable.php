<?php

declare(strict_types=1);

namespace App\Filament\Resources\Banners\Tables;

use App\Enums\MimeType;
use App\Filament\Resources\Banners\BannerResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->label(BannerResource::trans('columns.image'))
                    ->conversion(MimeType::IMAGE_WEBP->conversion()),

                TextColumn::make('title')
                    ->label(BannerResource::trans('columns.title'))
                    ->searchable(['title_en', 'title_ar']),

                TextColumn::make('link')
                    ->label(BannerResource::trans('columns.link'))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(BannerResource::trans('columns.created_at'))
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip('h:i A d/m/Y')
                    ->toggleable(),
            ])
            ->filters([])
            ->recordActions([ActionGroup::make([EditAction::make()])])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(BannerResource::trans('empty_state.heading'))
            ->emptyStateDescription(
                BannerResource::trans('empty_state.description'),
            )
            ->emptyStateIcon(Heroicon::OutlinedPhoto)
            ->emptyStateActions([CreateAction::make()]);
    }
}
