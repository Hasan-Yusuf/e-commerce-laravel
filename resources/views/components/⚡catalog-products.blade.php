<?php

use App\Models\Category;
use App\Services\Catalog\ProductCardAssembler;
use App\Services\Catalog\ProductQueryService;
use App\Services\Catalog\ProductSearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The products a catalogue listing narrows to: the toolbar over them — the
 * search, the count, the sort and the page style — and the grid or list itself.
 *
 * The filters are not here. The sidebar says what the listing is narrowed by and
 * announces it; this picks that up, so ticking a filter answers the products
 * while the sidebar is left alone, and searching, sorting or changing the view
 * is answered by this component alone — the sidebar is not even rendered for it.
 *
 * Like the filters, what it is told is the URL: the search, the sort, the view
 * and the page each carry `#[Url]`, so a listing is still a link somebody can be
 * sent. The narrowing filters arrive as the sidebar's announcement, and — with
 * no JavaScript to announce one — from the request the page was rendered with.
 *
 * The products arrive in batches. The grid holds the batches already read and a
 * control below them asks for the next one, which the scroll fires as it comes
 * into range; the same control is a link to the next batch, so a browser
 * without JavaScript pages through the listing a page at a time. The page in the
 * URL is how many batches have been read, so a link still lands where it was
 * cut, and a filter, a sort or a search starts over from the first one.
 */
new class extends Component {
    /**
     * How many products one batch holds, and so how many the listing grows by
     * each time another is read.
     */
    public const int PER_PAGE = 12;

    /**
     * How many products are taken to be above the fold. Their pictures are the
     * ones a visitor is waiting for, so they are fetched with the page rather
     * than as the listing is scrolled to.
     */
    public const int ABOVE_THE_FOLD = 4;

    #[Url(history: true)]
    public string $q = '';

    #[Url(history: true)]
    public string $sort = ProductQueryService::DEFAULT_SORT;

    #[Url(history: true)]
    public string $view = 'grid';

    /**
     * How many batches of products have been read, which is also how many are
     * shown: the listing grows downwards rather than replacing what is there.
     * Anything below one is read as the first batch (see batches()).
     *
     * It rides in the URL so the way on is a link — a browser without
     * JavaScript follows one to the next batch — but it does not push: reading
     * one more batch is not a place the shopper navigated to, so Back leaves
     * the listing rather than unwinding it batch by batch.
     */
    #[Url]
    public int $page = 1;

    /**
     * What the listing is narrowed by, as the accompanying filters last
     * announced it.
     *
     * @var array<string, mixed>
     */
    public array $filters = [];

    /**
     * The category this listing sits inside, or null for the whole shop.
     */
    public ?int $categoryId = null;

    /**
     * The resolved category, cached so a render that asks for it in the query,
     * the path and the count does not re-fetch the same row each time.
     */
    private ?Category $categoryModel = null;

    private ?int $categoryModelId = null;

    public function mount(?int $categoryId = null): void
    {
        $this->categoryId = $categoryId;

        $this->filters = $this->requestedFilters();
    }

    /**
     * A filter changed: what was asked of the products is asked again, from the
     * first batch — the third batch of the old listing is not the third of this
     * one.
     *
     * @param  array<string, mixed>  $filters
     */
    #[On('filters-updated')]
    public function syncFilters(array $filters): void
    {
        $this->filters = $filters;
        $this->page = 1;
    }

    /**
     * The batches read so far as one page of results. The listing grows
     * downwards, so what is shown is the first `page` batches, and reading one
     * more is asking for the same listing with room for twelve more products.
     *
     * One query for the products and one for their count, however many batches
     * have been read — the accumulated set, not one query per batch.
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $assembler = app(ProductCardAssembler::class);

        $query = app(ProductQueryService::class)->query(
            $this->params(),
            $this->category(),
        );

        // The list rows name the type; the grid cards do not, so the grid does
        // not pay for it. The brand — its name and logo — is on every card, so
        // the base query already loads it.
        if ($this->view === 'list') {
            $query->with('type');
        }

        $products = app(ProductSearchService::class)
            ->apply($query, $this->term())
            ->paginate(perPage: self::PER_PAGE * $this->batches(), page: 1);

        // One pass over the whole set, so neither the mapper nor a card's own
        // controls reads a product or a discount back per card.
        $assembler->remember($products->getCollection());

        return $products->through($assembler->mapper());
    }

    /**
     * Read one more batch, which the control below the listing asks for as it
     * comes into range.
     *
     * Reading past the end is harmless: the query simply returns the whole
     * listing, and the control stops being rendered once there is nothing after
     * it. Nothing is ever shown twice, because every batch is the first
     * `page * PER_PAGE` products rather than an offset into them.
     */
    public function loadMore(): void
    {
        $this->page = $this->batches() + 1;

        // `products` was computed for the batch we have just left, and a
        // computed property is cached for the rest of the request: drop it so
        // this render reads the batch we have just asked for.
        unset($this->products);
    }

    /**
     * The next batch as a link, which the control below the listing carries.
     * With JavaScript the scroll fires the control in place; without it, the
     * browser follows the link and the page comes back holding one more batch.
     */
    public function nextPageUrl(): string
    {
        return $this->listingPath()
        .'?'
        .http_build_query([
            ...$this->linkParams(),
            'page' => $this->batches() + 1,
        ]);
    }

    /**
     * How many batches the listing is showing.
     *
     * A page in the URL that is not a batch — zero, or below — is the first
     * one: it is the count of what has been read, so asking for it must never
     * read the whole catalogue at once.
     */
    private function batches(): int
    {
        return max(1, $this->page);
    }

    /**
     * The search term, normalized; empty when nothing was searched for, which is
     * what the toolbar's heading and the empty state turn on.
     */
    public function term(): string
    {
        return app(ProductSearchService::class)->normalize($this->q);
    }

    /**
     * A search, a sort or a view starts the listing over for the same reason a
     * filter does.
     */
    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->page = 1;
        }
    }

    /**
     * Every link back into this listing carries this: the state the query
     * service reads, and the query string the way to the next batch is built
     * from. What is empty, or already the default, is left out rather than
     * written down.
     *
     * @return array<string, mixed>
     */
    public function linkParams(): array
    {
        return array_filter(
            [
                ...Arr::except($this->params(), ['sort']),
                'sort' => $this->sort === ProductQueryService::DEFAULT_SORT
                    ? null
                    : $this->sort,
                'view' => $this->view === 'grid' ? null : $this->view,
            ],
            fn (mixed $value): bool => filled($value),
        );
    }

    /**
     * The URL of the listing itself, which its links and headings are built on.
     */
    public function listingPath(): string
    {
        $category = $this->category();

        return $category === null
            ? route('products.index')
            : route('category.show', $category);
    }

    /**
     * The state the products are asked with: what the listing is narrowed by,
     * and how it is ordered.
     *
     * @return array<string, mixed>
     */
    private function params(): array
    {
        return [
            ...$this->filters,
            'q' => $this->q,
            'sort' => $this->sort,
        ];
    }

    /**
     * What the request this page was rendered with was narrowed by. Reached when
     * the page is a posted filter — a browser without JavaScript has no
     * announcement to listen for.
     *
     * @return array<string, mixed>
     */
    private function requestedFilters(): array
    {
        return Arr::except(
            app(ProductQueryService::class)->requestParams(request()),
            ['sort'],
        );
    }

    private function category(): ?Category
    {
        if ($this->categoryId === null) {
            return null;
        }

        if ($this->categoryModelId !== $this->categoryId) {
            $this->categoryModel = Category::query()->find($this->categoryId);
            $this->categoryModelId = $this->categoryId;
        }

        return $this->categoryModel;
    }
};
?>

@php
    $term = $this->term();
    $listing = $this->listingPath();
    $linkParams = $this->linkParams();

    // The empty state only speaks about a search when there was one; otherwise
    // the catalogue's own "nothing here" wording does — the category's when the
    // listing sits inside one, the shop's when it does not.
    $emptyTitle = $term !== '' ? __('catalog.search.empty_title', ['query' => $term]) : null;
    $emptyMessage = match (true) {
        $term !== '' => __('catalog.search.empty_message'),
        $this->categoryId !== null => __('catalog.categories.empty'),
        default => null,
    };
    $emptyAction = $term !== '' ? __('catalog.search.clear') : null;
    $emptyHref = $term !== '' ? $listing : null;
@endphp

<div class="catalog-results">
    <div class="catalog-toolbar">
        @if ($this->categoryId === null)
            {{-- The search stays a form of its own: it is posted by the button
                 without JavaScript, and answered as it is typed with it. --}}
            <form class="search-toolbar" role="search" method="GET" action="{{ $listing }}" x-on:submit.prevent>
                <x-inputs.hidden-params :params="[...$linkParams, 'sort' => null, 'view' => null, 'q' => null]" />

                <label class="search-toolbar__label" for="catalog-search"> {{ __('catalog.search.label') }} </label>

                <div class="search-toolbar__field">
                    <input
                        class="search-toolbar__input"
                        id="catalog-search"
                        type="search"
                        name="q"
                        value="{{ $this->q }}"
                        wire:model.live.debounce.500ms="q"
                        placeholder="{{ __('catalog.search.placeholder') }}"
                        autocomplete="off"
                    />

                    <x-button variant="bare" class="search-toolbar__button" type="submit">
                        <x-icons.search-icon class="search-toolbar__icon" aria-hidden="true" />
                        <span class="visually-hidden"> {{ __('catalog.search.button') }} </span>
                    </x-button>
                </div>
            </form>
        @endif

        <p class="catalog-toolbar__count page-header__subtitle">
            {{
                $term !== ''
                ? trans_choice('catalog.search.results', $this->products->total(), ['query' => $term])
                : trans_choice('catalog.toolbar.count', $this->products->total())
            }}
        </p>

        <x-sort-select
            :action="$listing"
            :current="$this->sort"
            :wire-model="'sort'"
            :preserve="[
                ...Arr::except($linkParams, ['sort']),
                'q' => $term !== '' ? $term : null,
                'view' => $this->view === 'grid' ? null : $this->view,
            ]"
        />

        <x-view-toggle
            :action="$listing"
            :current="$this->view"
            :wire-model="'view'"
            :preserve="[
                ...Arr::except($linkParams, ['view']),
                'q' => $term !== '' ? $term : null,
                'sort' => $this->sort === \App\Services\Catalog\ProductQueryService::DEFAULT_SORT ? null : $this->sort,
            ]"
        />
    </div>

    {{-- The batches read so far. The container is stable from one batch to the
         next and the cards are keyed, so reading another batch appends to it:
         the cards already there are moved rather than repainted. It is marked
         busy while a batch is on its way, so the wait is announced rather than
         only shown. --}}
    <div class="catalog-items" wire:loading.attr="aria-busy" wire:target="loadMore">
        @if ($this->view === 'list')
            <x-product-list
                :products="$this->products"
                :empty-title="$emptyTitle"
                :empty="$emptyMessage"
                :empty-action="$emptyAction"
                :empty-href="$emptyHref"
                :priority-count="$this::ABOVE_THE_FOLD"
            />
        @else
            {{-- The page names this listing with its own heading, so the cards
                 are the headings directly under it. --}}
            <x-product-grid
                :products="$this->products"
                :empty-title="$emptyTitle"
                :empty="$emptyMessage"
                :empty-action="$emptyAction"
                :empty-href="$emptyHref"
                :heading-level="2"
                :priority-count="$this::ABOVE_THE_FOLD"
            />
        @endif
    </div>

    @if ($this->products->hasMorePages())
        {{-- The way on. `x-intersect` fires the control as it comes into range,
             so the next batch is read as the shopper approaches the bottom;
             the control is also a link, which is what pages the listing in a
             browser without JavaScript. --}}
        <div class="catalog-loader" x-intersect.margin.600px="$wire.loadMore()">
            <x-button
                variant="secondary"
                :href="$this->nextPageUrl()"
                wire:click.prevent="loadMore"
                wire:loading.attr="aria-disabled"
                wire:loading.class="is-loading"
                wire:target="loadMore"
            >
                {{ __('catalog.pagination.load_more') }}
            </x-button>

            {{-- A live region that stays on the page, so the wait is announced
                 where a reader is, not only drawn under the control. --}}
            <p class="catalog-loader__status" role="status" aria-live="polite">
                <span
                    class="catalog-loader__spinner"
                    aria-hidden="true"
                    wire:loading.delay
                    wire:target="loadMore"
                ></span>

                <span wire:loading.delay wire:target="loadMore"> {{ __('catalog.pagination.loading') }} </span>
            </p>
        </div>
    @elseif ($this->products->isNotEmpty())
        {{-- Everything matched is on the page: say so rather than leaving the
             shopper waiting for a batch that is never coming. --}}
        <p class="catalog-loader catalog-loader--end" role="status" aria-live="polite">
            {{ __('catalog.pagination.end') }}
        </p>
    @endif
</div>
