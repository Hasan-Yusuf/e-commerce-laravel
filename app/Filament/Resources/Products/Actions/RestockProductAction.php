<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Actions;

use App\Enums\SemanticColor;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class RestockProductAction extends Action
{
    #[\Override]
    public static function getDefaultName(): ?string
    {
        return 'restock';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(ProductResource::trans('actions.restock'))
            ->icon(Heroicon::ArrowTrendingUp)
            ->color(SemanticColor::Primary->value)
            ->tooltip(ProductResource::trans('actions.restock_tooltip'))
            ->schema([
                TextInput::make('quantity')
                    ->label(ProductResource::trans('actions.restock_quantity'))
                    ->helperText(ProductResource::trans('actions.restock_helper'))
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required()
                    ->live(),

                TextEntry::make('result')
                    ->label(ProductResource::trans('actions.restock_result'))
                    ->helperText(ProductResource::trans('helpers.restock_result'))
                    ->state(static function (Get $get, Product $record): string {
                        $quantity = (int) $get('quantity');

                        return sprintf(
                            '%d + %d = %d',
                            $record->stock,
                            $quantity,
                            $record->stock + $quantity,
                        );
                    }),
            ])
            ->action(static function (Product $record, array $data): void {
                app(ProductService::class)->restockProduct($record, (int) $data['quantity']);
            })
            ->successNotificationTitle(
                ProductResource::trans('notifications.restocked'),
            );
    }
}
