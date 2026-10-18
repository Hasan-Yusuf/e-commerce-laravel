<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductPricingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /**
     * Whether the page has already warmed the discount resolution for its rows.
     */
    private bool $pricingWarmed = false;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Warm the discount resolution once for the whole page before the rows price
     * themselves, so the discount column reads the shared per-request cache
     * rather than running a query pair per row (see ProductPricingService).
     */
    #[\Override]
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();

        if (! $this->pricingWarmed) {
            $this->pricingWarmed = true;

            /** @var Collection<int, Product> $products */
            $products = $records instanceof Collection
                ? $records
                : collect($records->items());

            app(ProductPricingService::class)->getDiscountsFor($products);
        }

        return $records;
    }
}
