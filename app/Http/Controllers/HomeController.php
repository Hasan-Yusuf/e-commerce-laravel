<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Catalog\BannerService;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\ProductService;
use Illuminate\View\View;

final class HomeController extends Controller
{
    /**
     * The storefront's front page: the hero slider, the categories rail, and the
     * product rails the catalogue is entered through.
     *
     * The queries are not written here. Each rail is a question about the
     * catalogue, so it is asked of the service that owns it — the banners of
     * `BannerService`, the categories of `CategoryService`, the products of
     * `ProductService` — and this reads as the list of what the page is made of.
     */
    public function __invoke(
        CategoryService $categoryService,
        ProductService $productService,
        BannerService $bannerService,
    ): View {
        return view('home', [
            'banners' => $bannerService->getHomePageBanners(),

            'categories' => $categoryService->getHomePageCategories(),

            // The product rails arrive as one array keyed by the name each is
            // read as, so the view keeps its own `$whatsNew` / `$onSale` /
            // `$newArrivals` without this naming them again.
            ...$productService->getHomePageSections(),
        ]);
    }
}
