<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Catalog\ProductPricingService;
use App\Services\Catalog\ProductSearchService;
use App\Services\Catalog\ProductService;
use App\Services\ImageService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductController extends Controller
{
    /**
     * The product listing. The heading's own wording is read here, because it is
     * the page's; the filters, the count and the products below it answer
     * themselves in the catalog component.
     */
    public function index(
        Request $request,
        ProductSearchService $search,
    ): View {
        return view('products.index', [
            'search' => $search->normalize($request->query('q')),
        ]);
    }

    public function show(
        Product $product,
        ProductPricingService $pricingService,
        ProductService $productService,
    ): View {
        abort_unless($product->isActive(), 404);

        // The specs table reads the type's spec definitions, and the
        // breadcrumbs read the category through the product's has-one-through;
        // the type's own category is not read on this page, so it is left off.
        $product->loadMissing(['brand.media', 'type.specDefinitions', 'category', 'media']);

        return view('products.show', [
            'product' => $product,
            'gallery' => ImageService::forProductGallery($product),
            'currentPrice' => Money::fromFils($pricingService->getCurrentPrice($product)),
            'discount' => $pricingService->getDiscount($product),
            'related' => $productService->relatedFor($product),
            'recommended' => $productService->recommendedFor($product),
        ]);
    }
}
