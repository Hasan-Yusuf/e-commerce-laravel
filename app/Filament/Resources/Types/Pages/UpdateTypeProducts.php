<?php

declare(strict_types=1);

namespace App\Filament\Resources\Types\Pages;

use App\Filament\Resources\Products\Schemas\ProductSpecFields;
use App\Filament\Resources\Types\TypeResource;
use App\Models\Product;
use App\Models\Type;
use App\Services\Catalog\ProductSpecSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;

/**
 * The products of a type, one group of the type's spec inputs each.
 *
 * A type's specs are the schema its products answer for, and the answers live on
 * the products, so changing the schema leaves them missing or wrongly shaped.
 * The edit page sends the admin here when that happens: every product under the
 * type is laid out with the inputs the schema now asks for, and saving the page
 * rebuilds each product's values from them — in schema order, mirrored into
 * every locale, and without the keys a spec that was removed used to hold.
 *
 * @property-read Schema $form
 */
class UpdateTypeProducts extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TypeResource::class;

    protected string $view = 'filament.resources.types.pages.update-type-products';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $type = $this->type();

        /** @var Collection<int, Product> $products */
        $products = $type->products()->orderBy('name')->get();

        $this->form->fill([
            'products' => $products
                ->mapWithKeys(static fn (Product $product): array => [
                    $product->id => [
                        'specs_en' => $product->specs_en ?? [],
                        'specs_ar' => $product->specs_ar ?? [],
                    ],
                ])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                Callout::make(TypeResource::trans('labels.update_products'))
                    ->description(
                        TypeResource::trans('helpers.update_products'),
                    )
                    ->warning(),

                ...$this->productGroups(),
            ])
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(TypeResource::trans('actions.save_products'))
                            ->tooltip(TypeResource::trans('actions.save_products_tooltip'))
                            ->submit('save'),
                    ]),
                ]),
        ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $type = $this->type();

        /** @var Collection<int, Product> $products */
        $products = $type->products()->get();

        foreach ($products as $product) {
            $values = $data['products'][$product->id] ?? [];

            $values = ProductSpecFields::mirrorNonLocalizedSpecValues([
                'type_id' => $type->id,
                'specs_en' => $values['specs_en'] ?? [],
                'specs_ar' => $values['specs_ar'] ?? [],
            ]);

            $product->update([
                'specs_en' => $values['specs_en'],
                'specs_ar' => $values['specs_ar'],
            ]);
        }

        app(ProductSpecSyncService::class)->markReviewed($type);

        Notification::make()
            ->success()
            ->title(TypeResource::trans('notifications.products_updated.title'))
            ->body(TypeResource::trans('notifications.products_updated.body'))
            ->send();

        $this->redirect(TypeResource::getUrl('edit', ['record' => $type]));
    }

    #[\Override]
    public function getTitle(): string
    {
        return TypeResource::trans('labels.update_products');
    }

    /**
     * One group per product of the type, each holding the inputs the type's
     * specs ask for.
     *
     * @return array<int, Section>
     */
    private function productGroups(): array
    {
        $type = $this->type();

        /** @var Collection<int, Product> $products */
        $products = $type->products()->orderBy('name')->get();

        return $products
            ->map(static fn (Product $product): Section => Section::make($product->name)
                ->description($product->sku)
                ->collapsible()
                ->columns(2)
                ->schema(ProductSpecFields::getSpecFields(
                    $type,
                    "products.{$product->id}.",
                )))
            ->all();
    }

    private function type(): Type
    {
        /** @var Type $type */
        return $this->getRecord()->load('specDefinitions');
    }
}
