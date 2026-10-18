<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\DeliveryMethod;
use App\Enums\Unit;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Forms\Components\UnitInput;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Rules\StockAvailable;
use App\Services\Order\OrderCalculator;
use App\Services\Order\OrderNumberService;
use App\Support\Money;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class OrderForm
{
    public static function configure(Schema $form): Schema
    {
        return $form->schema([
            self::leftColumn(),
            self::rightColumn(),
        ])
            ->columns(3);
    }

    private static function leftColumn(): Group
    {
        return Group::make()
            ->columnSpan(2)
            ->schema([
                self::customerAndStatusSection(),
                self::orderItemsRepeater(),
                self::additionalInformationSection(),
            ]);
    }

    private static function rightColumn(): Group
    {
        return Group::make()
            ->columnSpan(1)
            ->schema([
                self::shippingAndBillingSection(),
                self::paymentSummarySection(),
            ]);
    }

    private static function customerAndStatusSection(): Section
    {
        return Section::make(
            OrderResource::trans('sections.customer_and_order_status'),
        )
            ->columns(2)
            ->schema([
                Select::make('user_id')
                    ->label(OrderResource::trans('fields.user'))
                    ->relationship('user', 'email')
                    ->preload()
                    ->live(onBlur: true)
                    ->afterStateUpdated(static function (Set $set) {
                        $set('shipping_address_id', null);
                        $set('billing_address_id', null);
                    })
                    ->searchable()
                    ->required(),

                TextInput::make('number')
                    ->label(OrderResource::trans('fields.number'))
                    // The number the order will actually be given, asked of the
                    // service that allocates it.
                    ->default(
                        static fn (): string => app(
                            OrderNumberService::class,
                        )->peek(),
                    )
                    ->helperText(OrderResource::trans('helpers.number'))
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(ignoreRecord: true),
            ]);
    }

    private static function orderItemsRepeater(): Repeater
    {
        return Repeater::make('order_items')
            ->label(OrderResource::trans('sections.order_items'))
            ->itemLabel(static function (array $state) {
                $translatedLabel = OrderResource::trans('labels.order_item');

                if (($state['product_id'] ?? null) === null) {
                    return $translatedLabel;
                }

                $product = Product::find($state['product_id']);

                /** @var Product $product */
                return $product->name;
            })
            ->itemNumbers()
            ->relationship('orderItems')
            ->collapsible()
            ->minItems(1)
            ->columnSpanFull()
            ->addActionLabel(OrderResource::trans('labels.add_order_item'))
            ->live()
            ->afterStateUpdated(self::updateCalculations(...))
            ->columns(2)
            ->schema(self::orderItemFields());
    }

    /**
     * @return array<int, Select|TextInput|Grid>
     */
    private static function orderItemFields(): array
    {
        return [
            Select::make('product_id')
                ->label(OrderResource::trans('fields.product'))
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(static function (Set $set, $state) {
                    $product = Product::findSole($state);

                    /** @var Product $product */
                    $set('product_name', $product->name);
                    $set('sku', $product->sku);
                    $set('unit_price_in_fils', $product->price_in_fils->toAmount());
                    $set('quantity', 1, shouldCallUpdatedHooks: true);
                })
                ->required(),

            TextInput::make('sku')
                ->label(OrderResource::trans('fields.sku'))
                ->disabled()
                ->dehydrated(),

            self::orderItemPriceGrid(),
        ];
    }

    private static function orderItemPriceGrid(): Grid
    {
        return Grid::make(3)
            ->columnSpanFull()
            ->schema([
                MoneyInput::make('unit_price_in_fils')
                    ->label(OrderResource::trans('fields.unit_price'))
                    ->dehydrated()
                    ->default(0)
                    ->live()
                    ->disabled(static fn (Get $get) => blank($get('product_id')))
                    ->afterStateUpdated(self::updateRepeaterStates(...)),

                TextInput::make('quantity')
                    ->label(OrderResource::trans('fields.quantity'))
                    ->numeric()
                    ->default(0)
                    ->minValue(1)
                    ->live()
                    ->disabled(static fn (Get $get) => blank($get('product_id')))
                    ->hint(static function (Get $get, ?OrderItem $orderItem) {
                        if (! $get('product_id')) {
                            return null;
                        }

                        $product = Product::find($get('product_id'));

                        if (! $product) {
                            return null;
                        }

                        $heldQuantity = $orderItem->quantity ?? 0;

                        $maxQuantity = $product->getAvailableStock() + $heldQuantity;

                        return OrderResource::trans('hints.quantity', [
                            'value' => $maxQuantity,
                        ]);
                    })
                    ->afterStateUpdated(self::updateRepeaterStates(...))
                    ->rule(static function (Get $get, ?OrderItem $orderItem) {
                        $productId = $get('product_id');

                        $product = Product::findSole($productId);

                        if (! $product) {
                            return null;
                        }

                        return new StockAvailable($product, $orderItem);
                    })
                    ->required(),

                MoneyInput::make('total_price_in_fils')
                    ->label(OrderResource::trans('fields.total_price'))
                    ->disabled()
                    ->default(0)
                    ->dehydrated(),
            ]);
    }

    private static function additionalInformationSection(): Section
    {
        return Section::make(
            OrderResource::trans('sections.additional_information'),
        )->schema([
            Textarea::make('notes')
                ->label(OrderResource::trans('fields.notes'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    private static function shippingAndBillingSection(): Section
    {
        return Section::make(
            OrderResource::trans('sections.shipping_and_billing'),
        )->schema([
            Select::make('delivery_method')
                ->label(OrderResource::trans('fields.delivery_method'))
                ->options(DeliveryMethod::class)
                ->default(DeliveryMethod::STANDARD->value)
                ->selectablePlaceholder(false)
                ->required(),

            Toggle::make('shipping_and_billing_same')
                ->label(
                    OrderResource::trans('fields.shipping_and_billing_same'),
                )
                ->live()
                ->formatStateUsing(
                    static fn (?Order $order) => $order?->isShippingBillingSame(),
                )
                ->default(true),

            Select::make('shipping_address_id')
                ->label(OrderResource::trans('fields.shipping_address'))
                ->live()
                ->options(self::addressOptions(...))
                ->searchable()
                ->required(),

            Select::make('billing_address_id')
                ->label(OrderResource::trans('fields.billing_address'))
                ->live()
                ->options(self::addressOptions(...))
                ->hidden(
                    static fn (Get $get) => $get('shipping_and_billing_same'),
                )
                ->reactive()
                ->searchable()
                ->required(),
        ]);
    }

    /**
     * @return Collection<int|string, string>
     */
    private static function addressOptions(Get $get): Collection
    {
        $userId = $get('user_id');

        $user = $userId ? User::query()->with('addresses')->findSole($userId) : null;

        $addresses = $user !== null ? $user->addresses : collect();

        return $addresses
            ->pluck('address', 'id')
            ->map(static fn (mixed $address): string => (string) $address);
    }

    private static function paymentSummarySection(): Section
    {
        return Section::make(
            OrderResource::trans('sections.payment_summary'),
        )->schema([
            Toggle::make('paid_at')
                ->label(OrderResource::trans('fields.paid_at'))
                ->dehydrateStateUsing(
                    static fn ($state) => $state ? now() : null,
                ),

            MoneyInput::make('subtotal_in_fils')
                ->label(OrderResource::trans('fields.subtotal'))
                ->disabled()
                ->saved()
                ->dehydrated()
                ->default(0),

            UnitInput::make('tax_percentage')
                ->label(OrderResource::trans('fields.tax_percentage'))
                ->numeric()
                ->saved(false)
                ->unit(Unit::PERCENT)
                ->helperText(OrderResource::trans('helpers.tax_percentage'))
                ->live()
                ->afterStateUpdated(self::updateCalculations(...))
                ->default(0),

            MoneyInput::make('tax_amount_in_fils')
                ->label(OrderResource::trans('fields.tax_amount'))
                ->disabled()
                ->saved()
                ->dehydrated()
                ->default(0),

            MoneyInput::make('shipping_amount_in_fils')
                ->label(OrderResource::trans('fields.shipping_amount'))
                ->saved()
                ->dehydrated()
                ->live()
                ->afterStateUpdated(self::updateCalculations(...))
                ->default(0),

            MoneyInput::make('discount_amount_in_fils')
                ->label(OrderResource::trans('fields.discount_amount'))
                ->saved()
                ->dehydrated()
                ->live()
                ->afterStateUpdated(self::updateCalculations(...))
                ->default(0),

            MoneyInput::make('total_amount_in_fils')
                ->label(OrderResource::trans('fields.total_amount'))
                ->disabled()
                ->saved()
                ->dehydrated()
                ->default(0),
        ]);
    }

    protected static function updateRepeaterStates(Get $get, Set $set): void
    {
        $unitPrice = Money::fromBHD(self::toNumber($get('unit_price_in_fils')));

        $quantity = $get->integer('quantity');

        $set(
            'total_price_in_fils',
            $unitPrice->multiply($quantity)->toAmount(),
        );
    }

    /**
     * An amount a form carries, as a number. The field holds BHD, but a field
     * the form has not hydrated yet reads back as the record's Money.
     */
    private static function toNumber(mixed $state): float
    {
        return $state instanceof Money ? $state->toAmount() : (float) $state;
    }

    public static function updateCalculations(Get $get, Set $set): void
    {
        /** @var array<int|string, array{quantity?: int|string|null, unit_price_in_fils?: float|int|string|null}> $orderItems */
        $orderItems = $get->array('order_items');

        $lines = [];

        foreach ($orderItems as $item) {
            $lines[] = [
                'unit_price' => $item['unit_price_in_fils'] ?? 0,
                'quantity' => $item['quantity'] ?? 0,
            ];
        }

        $subtotal = OrderCalculator::subtotal($lines);

        $tax = OrderCalculator::tax(
            $subtotal,
            self::toNumber($get('tax_percentage')),
        );

        $shipping = Money::fromBHD(
            self::toNumber($get('shipping_amount_in_fils')),
        );

        $discount = Money::fromBHD(
            self::toNumber($get('discount_amount_in_fils')),
        );

        $total = OrderCalculator::total($subtotal, $tax, $shipping, $discount);

        $set('subtotal_in_fils', $subtotal->toAmount());

        $set('tax_amount_in_fils', $tax->toAmount());

        $set('total_amount_in_fils', $total->toAmount());
    }
}
