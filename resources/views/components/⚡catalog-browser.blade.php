<?php

use App\Models\Category;
use App\Services\Catalog\ActiveFilterChips;
use App\Services\Catalog\Filters\AvailabilityFilter;
use App\Services\Catalog\ProductQueryService;
use App\Services\Catalog\ProductSearchService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * What a catalogue listing is narrowed by: the filter sidebar and the chips that
 * stand for what is ticked in it — the filters, and their state.
 *
 * Ticking a filter used to post the sidebar's form and reload the page, so the
 * shopper lost their place in the grid and every change cost a whole page. The
 * filters are component state now, and that state is the URL: each one carries
 * `#[Url]`, so a change re-queries the listing while the address bar keeps
 * describing it — every way of browsing the catalogue is still a link somebody
 * can be sent, and Back steps through the filters the way it stepped through
 * pages.
 *
 * This component asks; the products container beside it answers. The filters
 * announce what they are narrowed to and the container picks it up, so ticking a
 * filter leaves the container to render the results alone, while a search, a
 * sort or a view is the container's own business — the sidebar is not rendered
 * for it at all.
 *
 * The sidebar keeps its form, its field names and its submit button, so a
 * browser without JavaScript posts the same query string and gets the same page
 * back; with JavaScript the button is hidden, the form is never submitted, and
 * the container follows along.
 *
 * Which listing this is, is the only thing it is told: a category scopes it to
 * that category's types, and no category means the whole shop.
 */
new class extends Component {
    // ── What the listing is narrowed to. Each of these rides in the URL. ──

    /** @var array<int, string> */
    #[Url(history: true)]
    public array $brand = [];

    /** @var array<int, string> */
    #[Url(history: true)]
    public array $category = [];

    /**
     * A list spec's chosen values as `[specId => [value, …]]`, a range spec's
     * bounds as `[specId => ['min' => …, 'max' => …]]`.
     *
     * @var array<string, mixed>
     */
    #[Url(history: true)]
    public array $specs = [];

    #[Url(history: true)]
    public ?string $price_min = null;

    #[Url(history: true)]
    public ?string $price_max = null;

    #[Url(history: true)]
    public ?string $availability = null;

    #[Url(history: true)]
    public ?string $discounted = null;

    // ── How the listing is presented, which the container holds. ──

    /**
     * The search, sort and page style the request carried. Read here only so a
     * filter posted without JavaScript keeps them: the container answers them
     * itself, and its own state is what they are with JavaScript.
     */
    public ?string $search = null;

    public ?string $sort = null;

    public ?string $view = null;

    /**
     * The category this listing sits inside, or null for the whole shop.
     */
    public ?int $categoryId = null;

    /**
     * The resolved category, cached so a render that asks for it in the query,
     * the path and the options does not re-fetch the same row each time.
     */
    private ?Category $categoryModel = null;

    private ?int $categoryModelId = null;

    public function mount(?int $categoryId = null): void
    {
        $this->categoryId = $categoryId;

        $request = request();

        $this->search = app(ProductSearchService::class)->normalize($request->query('q'));
        $this->sort = $request->query('sort');
        $this->view = $request->query('view');
    }

    /**
     * Every spec the sidebar is about to show needs its key to be there before
     * its control can bind, and in the shape that control answers with: a
     * checkbox group binds the list under the key and grows it from what it
     * finds, while a yes/no answers with the word the dropdown was given, which
     * starts as "not asked". Done before every render rather than once, because
     * which specs the sidebar shows follows the category.
     */
    public function rendering(): void
    {
        foreach ($this->specOptions as $spec) {
            $this->specs[$spec->id] ??= $spec->isBoolean() ? '' : [];
        }
    }

    /**
     * The applied filters, each with the way out its chip offers.
     *
     * @return array<int, array{label: string, url: string, filter: string, value: int|string|null}>
     */
    #[Computed]
    public function chips(): array
    {
        return app(ActiveFilterChips::class)->build(
            $this->linkParams(),
            $this->listingPath(),
        );
    }

    /** @return Collection<int, \App\Models\Brand> */
    #[Computed]
    public function brands(): Collection
    {
        return app(ProductQueryService::class)->brandOptions($this->category());
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return app(ProductQueryService::class)->categoryOptions();
    }

    /**
     * The specs the sidebar is about to offer. Not named `specs`, which the
     * property above has taken: that one is the listing's own selection.
     *
     * @return Collection<int, \App\DTOs\SpecFilterData>
     */
    #[Computed]
    public function specOptions(): Collection
    {
        return app(ProductQueryService::class)->specOptions($this->category());
    }

    /**
     * Ticking a filter is the whole announcement: what the listing is narrowed
     * to is said again, and the products container is what listens for it.
     */
    public function updated(string $property): void
    {
        $this->announceFilters();
    }

    /**
     * The lone "in stock" box: what a click on it means, and the same thing the
     * box means when the form posts it without JavaScript.
     */
    public function toggleAvailability(): void
    {
        $this->availability = $this->availability === AvailabilityFilter::IN_STOCK
            ? null
            : AvailabilityFilter::IN_STOCK;

        $this->announceFilters();
    }

    /**
     * The lone "discounted" box, whose filter the listing carries as the word
     * the form posts (`discounted=1`).
     */
    public function toggleDiscounted(): void
    {
        $this->discounted = filled($this->discounted) ? null : '1';

        $this->announceFilters();
    }

    /**
     * Drop everything the listing is narrowed by. How it is presented is the
     * products container's, and that survives.
     */
    public function clear(): void
    {
        $this->brand = [];
        $this->category = [];
        $this->specs = [];
        $this->price_min = null;
        $this->price_max = null;
        $this->availability = null;
        $this->discounted = null;

        $this->announceFilters();
    }

    /**
     * Drop one filter: what a chip does when it is clicked, and the same thing
     * its link does with a page load. The rest of the listing survives.
     */
    public function removeFilter(string $filter, int|string|null $value = null): void
    {
        match ($filter) {
            'brand' => $this->brand = [],
            'category' => $this->category = [],
            'price' => [$this->price_min, $this->price_max] = [null, null],
            'availability' => $this->availability = null,
            'discounted' => $this->discounted = null,
            'specs' => $this->specs = Arr::except($this->specs, [$value]),
            default => null,
        };

        $this->announceFilters();
    }

    /**
     * The sidebar's own submit. With JavaScript the filters have already
     * answered, so all that is left is to ask the container to start over.
     */
    public function apply(): void
    {
        $this->announceFilters();
    }

    /**
     * Every link back into this listing carries this: the filters it is narrowed
     * by, and the presentation the request came with so a posted filter or a
     * followed chip keeps the search, the sort and the view.
     *
     * @return array<string, mixed>
     */
    public function linkParams(): array
    {
        return array_filter(
            [...$this->params(), ...$this->presentedParams()],
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
     * The state the filters are asked with.
     *
     * @return array<string, mixed>
     */
    private function params(): array
    {
        return [
            'brand' => $this->brand,
            'category' => $this->category,
            // The keys seeded for the sidebar are empty until a choice is made
            // under them: asked with, they narrow nothing.
            'specs' => array_filter($this->specs, fn (mixed $value): bool => filled($value)),
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'availability' => $this->availability,
            'discounted' => $this->discounted,
        ];
    }

    /**
     * What the page was presented with, which the sidebar's own form has to
     * carry on when it is posted without JavaScript.
     *
     * @return array<string, mixed>
     */
    private function presentedParams(): array
    {
        return [
            'q' => $this->search,
            'sort' => $this->sort,
            'view' => $this->view,
        ];
    }

    /**
     * Say what the listing is narrowed to, for the products container to answer.
     */
    private function announceFilters(): void
    {
        $this->dispatch('filters-updated', filters: $this->params());
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
    $listing = $this->listingPath();
    $presented = array_filter(
        $this->presentedParams(),
        fn (mixed $value): bool => filled($value),
    );
@endphp

<div class="catalog-browser">
    <x-active-filters :filters="$this->chips" />

    <div class="catalog-layout">
        <x-filter-sidebar :action="$listing" :preserve="$presented">
            @if ($this->categoryId === null && $this->categories->isNotEmpty())
                <x-filter-dropdown
                    :title="__('catalog.filters.category')"
                    name="category[]"
                    wire-model="category"
                    :options="$this->categories->pluck('name', 'id')->all()"
                    :selected="$this->category"
                />
            @endif

            @if ($this->brands->isNotEmpty())
                <x-filter-dropdown
                    :title="__('catalog.filters.brand')"
                    name="brand[]"
                    wire-model="brand"
                    :options="$this->brands->pluck('name', 'id')->all()"
                    :selected="$this->brand"
                />
            @endif

            <x-spec-filters :specs="$this->specOptions" :filters="['specs' => $this->specs]" />

            <x-filter-group :title="__('catalog.filters.price')">
                <div class="filter-price">
                    <label class="visually-hidden" for="price-min"> {{ __('catalog.filters.price_min') }} </label>
                    <input
                        type="number"
                        id="price-min"
                        name="price_min"
                        min="0"
                        step="0.001"
                        value="{{ $this->price_min }}"
                        wire:model.live.debounce.500ms="price_min"
                        placeholder="{{ __('catalog.filters.price_min') }}"
                    />

                    <label class="visually-hidden" for="price-max"> {{ __('catalog.filters.price_max') }} </label>
                    <input
                        type="number"
                        id="price-max"
                        name="price_max"
                        min="0"
                        step="0.001"
                        value="{{ $this->price_max }}"
                        wire:model.live.debounce.500ms="price_max"
                        placeholder="{{ __('catalog.filters.price_max') }}"
                    />
                </div>
            </x-filter-group>

            {{-- The two lone questions are not groups: one answer each, so the
                 box and the word beside it are the whole filter. --}}
            <div class="filter-toggles">
                <x-inputs.checkbox
                    name="availability"
                    value="in_stock"
                    wire:click="toggleAvailability"
                    :label="__('catalog.filters.in_stock')"
                    wrapper-class="filter-checkbox"
                    :checked="$this->availability === 'in_stock'"
                />

                <x-inputs.checkbox
                    name="discounted"
                    value="1"
                    wire:click="toggleDiscounted"
                    :label="__('catalog.filters.discounted')"
                    wrapper-class="filter-checkbox"
                    :checked="filled($this->discounted)"
                />
            </div>

            {{-- The lone questions are a filter too, so they are ruled off from
                 the one before them and from the actions under them. --}}
            <hr class="separator separator--horizontal filter-separator" />
        </x-filter-sidebar>

        {{-- The products answer the filters rather than being rendered with them:
             the listing's own container, which picks up what is announced here
             and updates itself, so a search or a sort never reaches the sidebar
             and a tick reaches nothing else. --}}
        <livewire:catalog-products :category-id="$this->categoryId" />
    </div>
</div>
