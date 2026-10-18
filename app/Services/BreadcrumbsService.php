<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryTreeService;
use Illuminate\Http\Request;

/**
 * Builds the breadcrumb trail from the request's matched route, so pages do
 * not have to assemble it by hand. Category trails follow the category tree
 * (root → … → current), and a product trail inserts its category branch
 * before the product itself.
 */
class BreadcrumbsService
{
    public function __construct(
        private Request $request,
        private CategoryTreeService $categoryTreeService,
    ) {}

    /**
     * The breadcrumb trail for the current request.
     *
     * @return array<int, array{label: string, url?: string}>
     */
    public function forCurrentRoute(): array
    {
        return [$this->home(), ...$this->currentRouteCrumbs()];
    }

    /**
     * @return array<int, array{label: string, url?: string}>
     */
    private function currentRouteCrumbs(): array
    {
        return match ($this->request->route()?->getName()) {
            'products.index' => [
                $this->label(
                    $this->hasSearchTerm()
                        ? 'catalog.search.title'
                        : 'catalog.products.index_title',
                ),
            ],
            'products.show' => $this->productCrumbs(),
            'category.show' => $this->categoryCrumbs(),
            'category.index' => [$this->label('homepage.categories.title')],
            'cart.index' => [$this->label('carts.title')],
            'contact.index' => [$this->label('contact.title')],
            'checkout.index', 'checkout.step' => [
                ['label' => __('carts.title'), 'url' => route('cart.index')],
                $this->label('checkout.title'),
            ],
            // An order is placed from the checkout and looked at from the
            // account's own list: each trail is a fixed pair of names, read from
            // the route rather than from any order the route was given.
            'checkout.confirmation' => [
                $this->label('orders.confirmation.title'),
            ],
            'account.index' => [$this->label('account.title')],
            'account.edit' => [
                [
                    'label' => __('account.title'),
                    'url' => route('account.index'),
                ],
                $this->label('account.profile.breadcrumb'),
            ],
            'orders.index' => [$this->label('orders.title')],
            'orders.show' => [
                ['label' => __('orders.title'), 'url' => route('orders.index')],
                $this->label('orders.show.breadcrumb'),
            ],
            default => [],
        };
    }

    /**
     * @return array<int, array{label: string, url?: string}>
     */
    private function productCrumbs(): array
    {
        $product = $this->request->route('product');

        if (! $product instanceof Product) {
            return [];
        }

        $category = $product->category;

        $crumbs = $category instanceof Category
            ? $this->categoryCrumbsFor($category)
            : [];

        $crumbs[] = ['label' => $product->name];

        return $crumbs;
    }

    /**
     * @return array<int, array{label: string, url?: string}>
     */
    private function categoryCrumbs(): array
    {
        $category = $this->request->route('category');

        if (! $category instanceof Category) {
            return [];
        }

        return $this->categoryCrumbsFor($category);
    }

    /**
     * @return array<int, array{label: string, url?: string}>
     */
    private function categoryCrumbsFor(Category $category): array
    {
        return array_map(
            static fn (Category $node): array => [
                'label' => $node->name,
                'url' => route('category.show', $node->slug),
            ],
            $this->categoryBranch($category),
        );
    }

    /**
     * The category and all of its ancestors, ordered root-first.
     *
     * @return array<int, Category>
     */
    private function categoryBranch(Category $category): array
    {
        return [...$this->categoryTreeService->ancestors($category)->all(), $category];
    }

    /**
     * Whether the current request carries a non-empty search term.
     */
    private function hasSearchTerm(): bool
    {
        $query = $this->request->query('q');

        return is_string($query) && trim($query) !== '';
    }

    /**
     * @return array{label: string, url: string}
     */
    private function home(): array
    {
        return [
            'label' => __('catalog.breadcrumbs.home'),
            'url' => route('home'),
        ];
    }

    /**
     * @return array{label: string}
     */
    private function label(string $key): array
    {
        return ['label' => __($key)];
    }
}
