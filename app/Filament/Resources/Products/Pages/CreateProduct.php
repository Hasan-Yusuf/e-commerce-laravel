<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Schemas\ProductSpecFields;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateProduct extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ProductResource::class;

    #[\Override]
    public function mount(): void
    {
        parent::mount();

        $copyId = request()->query('copy_id');

        if (! $copyId) {
            return;
        }

        /** @var Product|null $productToCopy */
        $productToCopy = Product::query()
            ->with(['brand', 'category', 'type'])
            ->findSole(
                $copyId,
            );

        if (! $productToCopy) {
            return;
        }

        /** @var array<string, mixed> $data */
        $data = $productToCopy->toArray();

        $data['name'] .= '( copy )';
        $data['slug'] .= '-copy';

        unset($data['sku'], $data['id']);

        $this->form->fill($data);
    }

    public function getSteps(): array
    {
        return ProductForm::getWizardSteps();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ProductSpecFields::mirrorNonLocalizedSpecValues($data);
    }
}
