<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Normalizes a storefront search term and applies it to a product query,
 * matching the product name, meta name and SKU, the English and Arabic brand
 * names, and — because a shopper who types "phone" or "furniture" is looking for
 * what those hold rather than for a product that carries the word — the kind of
 * product it is and the category its type sits on, subcategories included.
 *
 * The product's own columns are matched with a bound LIKE carrying an explicit
 * `ESCAPE '!'` clause: `%` and `_` typed by the user are matched literally
 * rather than as wildcards, and the escape behaviour is portable across SQLite
 * and MySQL. Laravel's query builder has no first-class `LIKE ... ESCAPE` API,
 * so those clauses use whereRaw; the values are still passed as bindings.
 */
class ProductSearchService
{
    public function __construct(
        private CategoryTreeService $categoryTreeService,
    ) {}

    /**
     * The minimum length for a term to count as a search. Shorter input falls
     * back to browsing the catalog.
     */
    public const int MIN_SEARCH_LENGTH = 2;

    /**
     * The maximum accepted search term length. Longer terms simply match
     * nothing rather than being sent to the database.
     */
    public const int MAX_SEARCH_LENGTH = 100;

    /**
     * Normalize raw user input into a search term. Non-strings (e.g.
     * `q[]=...`) and terms shorter than MIN_SEARCH_LENGTH become an empty
     * string, which callers treat as "no search".
     */
    public function normalize(mixed $raw): string
    {
        if (! is_string($raw)) {
            return '';
        }

        $term = trim($raw);

        return mb_strlen($term) >= self::MIN_SEARCH_LENGTH ? $term : '';
    }

    /**
     * Apply the (already normalized) term to the query. An empty term is a
     * no-op, keeping the whole catalog browsable.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function apply(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        if (mb_strlen($term) > self::MAX_SEARCH_LENGTH) {
            return $query->whereRaw('1 = 0');
        }

        $like = '%'.$this->escapeLike($term).'%';
        $categories = $this->matchingCategoryIds($like);

        return $query->where(static function (Builder $query) use ($like, $categories): void {
            $query
                ->whereRaw("products.name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("products.meta_name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("products.sku LIKE ? ESCAPE '!'", [$like])
                ->orWhereHas('brand', static function (Builder $brand) use ($like): void {
                    $brand->whereRaw("brands.name_en LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("brands.name_ar LIKE ? ESCAPE '!'", [
                            $like,
                        ]);
                })
                // What the product is, and the shelf it sits on: its type, or a
                // category its type belongs to.
                ->orWhereHas('type', static function (Builder $type) use ($like, $categories): void {
                    $type->where('types.name', 'like', $like)
                        ->orWhereIn('types.category_id', $categories);
                });
        });
    }

    /**
     * The categories whose own name matches, each with the ids of everything
     * under it: a shopper who names a category is looking for what is anywhere
     * in it, not only for what hangs on it directly.
     *
     * @return array<int, int>
     */
    private function matchingCategoryIds(string $like): array
    {
        return Category::query()
            ->where(
                static fn (Builder $categories): Builder => $categories->where('categories.name_en', 'like', $like)
                    ->orWhere('categories.name_ar', 'like', $like),
            )
            ->get()
            ->flatMap(fn (Category $category): array => $this->categoryTreeService->descendantIds($category))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Escape the LIKE wildcards (`%`, `_`) and the escape character itself so
     * user input only ever matches literally.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
