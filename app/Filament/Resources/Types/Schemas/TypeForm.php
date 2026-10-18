<?php

declare(strict_types=1);

namespace App\Filament\Resources\Types\Schemas;

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Types\TypeResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TypeForm
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
                TextInput::make('name')
                    ->label(TypeResource::trans('fields.name'))
                    ->placeholder(TypeResource::trans('placeholders.name'))
                    ->string()
                    ->rule('min:2')
                    ->required(),

                Select::make('category_id')
                    ->label(TypeResource::trans('fields.category'))
                    ->relationship('category')
                    ->getOptionLabelFromRecordUsing(
                        static fn (Category $category) => $category->name,
                    )
                    ->searchable()
                    ->preload()
                    ->createOptionForm([...CategoryForm::getFormSchema()])
                    ->createOptionAction(
                        static fn (Action $action) => $action->slideOver(),
                    )
                    ->required(),

                static::getSpecDefinitionsRepeater()->columnSpanFull(),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public static function getSpecDefinitionsRepeater(): Repeater
    {
        return SpecDefinitionsRepeater::make();
    }
}
