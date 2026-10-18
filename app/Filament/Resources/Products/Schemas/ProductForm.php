<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\MimeType;
use App\Enums\Unit;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Forms\Components\UnitInput;
use App\Filament\Resources\Brands\Schemas\BrandForm;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Types\Schemas\TypeForm;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

/**
 * The product form: the create/edit wizard's steps and the inputs they hold.
 * The resource's own `form()` stays empty because the wizard page wraps the
 * steps into the schema, so the fields live together here.
 */
class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    /**
     * @return array<int, Step>
     */
    public static function getWizardSteps(): array
    {
        return [
            ...self::getMainInformationStep(),
            ...self::getConfigurationStep(),
        ];
    }

    /**
     * @return array<int, Step>
     */
    public static function getMainInformationStep(): array
    {
        return [
            Step::make('Main Information')
                ->label(ProductResource::trans('steps.main'))
                ->schema([
                    Section::make([
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->label(ProductResource::trans('fields.name'))
                                    ->autocapitalize()
                                    ->placeholder(
                                        ProductResource::trans('placeholders.name'),
                                    )
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(static function (
                                        Set $set,
                                        $operation,
                                        $state,
                                    ) {
                                        if ($operation === Operation::Edit) {
                                            return;
                                        }
                                        $set('slug', str()->slug($state));
                                    }),

                                TextInput::make('slug')
                                    ->label(ProductResource::trans('fields.slug'))
                                    ->placeholder(
                                        ProductResource::trans('placeholders.slug'),
                                    )
                                    ->helperText(ProductResource::trans('helpers.slug'))
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->disabled(),

                                TextInput::make('sku')
                                    ->label(ProductResource::trans('fields.sku'))
                                    ->helperText(
                                        ProductResource::trans('helpers.sku_format'),
                                    )
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true)
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Textarea::make('description_en')
                                    ->label(ProductResource::trans('fields.desc_en'))
                                    ->placeholder(
                                        ProductResource::trans('placeholders.desc_en'),
                                    )
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->required(),

                                Textarea::make('description_ar')
                                    ->label(ProductResource::trans('fields.desc_ar'))
                                    ->placeholder(
                                        ProductResource::trans('placeholders.desc_ar'),
                                    )
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),

                        MoneyInput::make('price_in_fils')
                            ->label(ProductResource::trans('fields.price'))
                            ->placeholder(ProductResource::trans('placeholders.price'))
                            ->required()
                            ->rule('gt:0'),

                        MoneyInput::make('cost_price_in_fils')
                            ->label(ProductResource::trans('fields.cost_price'))
                            ->placeholder(
                                ProductResource::trans('placeholders.cost_price'),
                            )
                            ->required()
                            ->rule('gt:0'),

                        SpatieMediaLibraryFileUpload::make('Thumbnail')
                            ->label(ProductResource::trans('fields.thumbnail'))
                            ->image()
                            ->helperText(ProductResource::trans('helpers.image'))
                            ->collection('thumbnail')
                            ->responsiveImages()
                            ->conversion(MimeType::IMAGE_WEBP->conversion())
                            ->required(),

                        SpatieMediaLibraryFileUpload::make('Images')
                            ->image()
                            ->label(ProductResource::trans('fields.images'))
                            ->helperText(ProductResource::trans('helpers.image'))
                            ->collection('gallery')
                            ->multiple()
                            ->responsiveImages()
                            ->conversion(MimeType::IMAGE_WEBP->conversion()),
                    ])->columns(2),
                ]),
        ];
    }

    /**
     * @return array<int, Step>
     */
    public static function getConfigurationStep(): array
    {
        return [
            Step::make(ProductResource::trans('steps.configuration'))->schema([
                Section::make([
                    Select::make('brand_id')
                        ->label(ProductResource::trans('fields.brand'))
                        ->placeholder(ProductResource::trans('placeholders.choose_brand'))
                        ->relationship('brand', 'name_'.app()->getLocale())
                        ->searchable()
                        ->preload()
                        ->createOptionForm(BrandForm::getFormSchema())
                        ->createOptionAction(
                            static fn (Action $action) => $action->slideOver(),
                        ),

                    Select::make('type_id')
                        ->label(ProductResource::trans('fields.type'))
                        ->placeholder(ProductResource::trans('placeholders.choose_type'))
                        ->relationship('type', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm(TypeForm::getFormSchema())
                        ->createOptionAction(
                            static fn (Action $action) => $action->slideOver(),
                        )
                        ->live()
                        ->afterStateUpdated(static function (Set $set): void {
                            $set('specs_en', []);
                            $set('specs_ar', []);
                        })
                        ->required(),

                    Section::make(ProductResource::trans('fields.specifications'))
                        ->description(ProductResource::trans('helpers.specifications'))
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema(
                            static fn (Get $get): array => ProductSpecFields::getTypeSpecFields(
                                $get('type_id'),
                            ),
                        ),

                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            UnitInput::make('weight')
                                ->label(ProductResource::trans('fields.weight'))
                                ->unit(Unit::WEIGHT)
                                ->default(0)
                                ->numeric(),

                            TextInput::make('stock')
                                ->label(ProductResource::trans('fields.stock'))
                                ->default(0)
                                ->numeric()
                                ->rules(['int'])
                                ->required(),

                            TextInput::make('low_stock_threshold')
                                ->label(
                                    ProductResource::trans('fields.low_stock_threshold'),
                                )
                                ->numeric()
                                ->default(5)
                                ->helperText(
                                    ProductResource::trans('helpers.low_stock_threshold'),
                                )
                                ->required(),
                        ]),

                    Section::make(ProductResource::trans('sections.featured.header'))
                        ->description(ProductResource::trans('sections.featured.desc'))
                        ->schema([
                            Toggle::make('featured')
                                ->label(ProductResource::trans('fields.featured'))
                                ->live()
                                ->afterStateUpdated(static fn ($state, Set $set) => $set(
                                    'featured_at',
                                    $state ? now() : null,
                                ))
                                ->formatStateUsing(
                                    static fn (?Product $record): bool => $record?->isFeatured() ?? false,
                                )
                                ->dehydrated(false)
                                ->default(false),

                            // Its own default is left empty: a product is featured
                            // only when the administrator says so.
                            DatePicker::make('featured_at')
                                ->label(ProductResource::trans('fields.featured_at'))
                                ->displayFormat('d/m/Y')
                                ->disabled(static fn (Get $get) => $get('featured'))
                                ->dehydrated()
                                ->live(),
                        ]),

                    Section::make(ProductResource::trans('sections.activated.header'))
                        ->description(ProductResource::trans('sections.activated.desc'))
                        ->schema([
                            Toggle::make('active')
                                ->label(ProductResource::trans('fields.active'))
                                ->live()
                                ->afterStateUpdated(static fn ($state, Set $set) => $set(
                                    'activated_at',
                                    $state ? now() : null,
                                ))
                                ->formatStateUsing(
                                    static fn (?Product $record): bool => $record?->isActive() ?? false,
                                )
                                ->dehydrated(false)
                                ->default(true),

                            // Today, so a product created with the toggle on is
                            // on sale at once and the date shown is the one saved.
                            DatePicker::make('activated_at')
                                ->label(ProductResource::trans('fields.activated_at'))
                                ->default(now())
                                ->displayFormat('d/m/Y')
                                ->disabled(static fn (Get $get) => $get('active'))
                                ->dehydrated()
                                ->live(),
                        ]),
                ])->columns(2),
            ]),
        ];
    }
}
