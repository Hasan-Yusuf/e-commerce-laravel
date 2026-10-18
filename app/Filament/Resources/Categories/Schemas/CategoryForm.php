<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\MimeType;
use App\Enums\SemanticColor;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CategoryForm
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
            Group::make([
                Section::make(CategoryResource::trans('sections.main'))->schema(
                    [
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name_en')
                                    ->label(
                                        CategoryResource::trans(
                                            'fields.name_en',
                                        ),
                                    )
                                    ->autocapitalize()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(static function (
                                        Set $set,
                                        $operation,
                                        $state,
                                    ) {
                                        if ($operation === 'Edit') {
                                            return;
                                        }
                                        $set('slug', str()->slug($state));
                                    }),

                                TextInput::make('name_ar')
                                    ->label(
                                        CategoryResource::trans(
                                            'fields.name_ar',
                                        ),
                                    )
                                    ->autocapitalize()
                                    ->required(),

                                TextInput::make('slug')
                                    ->label(
                                        CategoryResource::trans('fields.slug'),
                                    )
                                    ->helperText(
                                        CategoryResource::trans('helpers.slug'),
                                    )
                                    ->required()
                                    ->dehydrated()
                                    ->disabled(),
                            ]),

                        MarkdownEditor::make('description_en')
                            ->label(
                                CategoryResource::trans(
                                    'fields.description_en',
                                ),
                            )
                            ->required()
                            ->fileAttachmentsDirectory('Categories')
                            ->fileAttachmentsDisk('public')
                            ->columnSpanFull(),

                        MarkdownEditor::make('description_ar')
                            ->label(
                                CategoryResource::trans(
                                    'fields.description_ar',
                                ),
                            )
                            ->fileAttachmentsDirectory('Categories')
                            ->fileAttachmentsDisk('public')
                            ->columnSpanFull(),
                    ],
                ),
            ]),

            Group::make([
                Section::make(CategoryResource::trans('sections.settings'))
                    ->schema([
                        Select::make('parent')
                            ->label(CategoryResource::trans('fields.parent'))
                            ->relationship(
                                'parent',
                                'name_'.app()->getLocale(),
                            )
                            ->searchable()
                            ->preload()
                            ->helperText(
                                CategoryResource::trans('helpers.parent'),
                            )
                            ->columnSpanFull(),

                        Toggle::make('activated_at')
                            ->label(
                                CategoryResource::trans('fields.activated_at'),
                            )
                            ->offColor(SemanticColor::Danger->value)
                            ->default(true)
                            ->helperText(
                                CategoryResource::trans('helpers.activated_at'),
                            )
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(
                                static fn ($state) => $state ? now() : null,
                            ),

                        TextInput::make('sort_order')
                            ->label(
                                CategoryResource::trans('fields.sort_order'),
                            )
                            ->numeric()
                            ->default(0)
                            ->helperText(
                                CategoryResource::trans('helpers.sort_order'),
                            ),

                        SpatieMediaLibraryFileUpload::make('Image')
                            ->label(CategoryResource::trans('fields.image'))
                            ->image()
                            ->helperText(CategoryResource::trans('helpers.image'))
                            ->conversion(MimeType::IMAGE_WEBP->conversion())
                            ->collection('thumbnail')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]),
        ];
    }
}
