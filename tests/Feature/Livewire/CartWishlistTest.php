<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\WishlistItem;
use App\Services\Cart\CartService;
use App\Services\Wishlist\WishlistService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The wishlist section rendered under the cart, including the moves that go in
 * both directions between the two lists.
 */
class CartWishlistTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_renders_the_wishlist_section_under_the_cart(): void
    {
        [$itemId, $product] = $this->seedSavedProduct();

        Livewire::test('cart-page')
            ->assertSee(__('wishlist.title'))
            ->assertSee($product->name)
            ->assertSee('class="wishlist-section wrapper"', false)
            ->assertSee('wire:key="wishlist-card-'.$itemId.'"', false)
            ->assertSee(__('wishlist.actions.move_to_cart'));
    }

    public function test_the_wishlist_renders_even_when_the_cart_is_empty(): void
    {
        $product = $this->saveProduct();

        Livewire::test('cart-page')
            ->assertSee(__('carts.empty.title'))
            ->assertSee(__('wishlist.title'))
            ->assertSee($product->name);
    }

    public function test_it_explains_an_empty_wishlist(): void
    {
        Livewire::test('cart-page')->assertSee(__('wishlist.empty.message'));
    }

    public function test_moving_an_entry_to_the_cart_adds_it_and_drops_the_entry(): void
    {
        [$itemId, $product] = $this->seedSavedProduct();

        Livewire::test('cart-page')
            ->call('moveToCart', $itemId)
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated')
            ->assertSee(__('wishlist.empty.message'));

        $this->assertDatabaseCount('wishlist_items', 0);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_moving_an_out_of_stock_entry_keeps_it_on_the_wishlist(): void
    {
        [$itemId, $product] = $this->seedSavedProduct(stock: 0);

        Livewire::test('cart-page')
            ->call('moveToCart', $itemId)
            ->assertDispatched('notify')
            ->assertNotDispatched('cart-updated')
            ->assertSee($product->name);

        $this->assertDatabaseHas('wishlist_items', ['id' => $itemId]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_moving_an_entry_clamps_to_stock(): void
    {
        [$itemId, $product] = $this->seedSavedProduct(stock: 1);

        // The cart already holds the only unit, so the move cannot add more.
        app(CartService::class)->add($product, 1);

        Livewire::test('cart-page')
            ->call('moveToCart', $itemId)
            ->assertDispatched('notify');

        static::assertSame(
            1,
            (int) CartItem::query()
                ->where('product_id', $product->id)
                ->value('quantity'),
        );
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_removing_an_entry_drops_it(): void
    {
        [$itemId, $product] = $this->seedSavedProduct();

        Livewire::test('cart-page')
            ->call('removeFromWishlist', $itemId)
            ->assertDontSee($product->name)
            ->assertDispatched('notify')
            ->assertSee(__('wishlist.empty.message'));

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_a_gone_entry_is_shown_as_unavailable_with_a_remove_control(): void
    {
        [$itemId, $product] = $this->seedSavedProduct();

        $product->delete();

        Livewire::test('cart-page')
            ->assertSee(__('wishlist.item.unavailable_product'))
            ->assertSee('wishlist-card--gone', false)
            ->assertSee('removeFromWishlist('.$itemId.')', false);
    }

    public function test_an_entry_from_another_list_is_not_addressable(): void
    {
        $foreign = WishlistItem::factory()
            ->forSession('AnotherCartWishlistSession012345678901234')
            ->forProduct(Product::factory()->active()->create())
            ->create();

        Livewire::test('cart-page')
            ->call('moveToCart', $foreign->id)
            ->call('removeFromWishlist', $foreign->id);

        $this->assertDatabaseHas('wishlist_items', ['id' => $foreign->id]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_it_renders_a_move_all_to_cart_button(): void
    {
        $this->seedSavedProduct();

        Livewire::test('cart-page')
            ->assertSee(
                'action="'.route('wishlist.moveAllToCart').'"',
                false,
            )
            ->assertSee('moveAllToCart', false)
            ->assertSee(__('wishlist.actions.move_all_to_cart'));
    }

    public function test_the_move_all_button_is_hidden_when_the_wishlist_is_empty(): void
    {
        Livewire::test('cart-page')->assertDontSee(
            __('wishlist.actions.move_all_to_cart'),
        );
    }

    public function test_moving_the_whole_wishlist_into_the_cart_empties_it(): void
    {
        [, $first] = $this->seedSavedProduct();
        [, $second] = $this->seedSavedProduct();

        Livewire::test('cart-page')
            ->call('moveAllToCart')
            // The list empties...
            ->assertSee(__('wishlist.empty.message'))
            // ...and both products show up as cart lines.
            ->assertSee($first->name)
            ->assertSee($second->name)
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated');

        $this->assertDatabaseCount('wishlist_items', 0);
        $this->assertDatabaseHas('cart_items', ['product_id' => $first->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $second->id]);
    }

    public function test_moving_the_whole_wishlist_keeps_the_unavailable_entries(): void
    {
        [$soldOutId, $soldOut] = $this->seedSavedProduct(stock: 0);

        Livewire::test('cart-page')
            ->call('moveAllToCart')
            ->assertSee($soldOut->name)
            ->assertDispatched('notify')
            // Nothing reached the cart, so the header badge has nothing to refresh.
            ->assertNotDispatched('cart-updated');

        $this->assertDatabaseHas('wishlist_items', ['id' => $soldOutId]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_it_wires_no_js_fallback_forms_to_the_wishlist_routes(): void
    {
        [$itemId] = $this->seedSavedProduct();

        $item = WishlistItem::query()->findOrFail($itemId);

        Livewire::test('cart-page')
            // Move: a real form that posts when Livewire is not running.
            ->assertSee(
                'action="'.route('wishlist.moveToCart', $item).'"',
                false,
            )
            ->assertSee('moveToCart('.$itemId.')', false)
            // Remove: posts as a DELETE.
            ->assertSee(
                'action="'.route('wishlist.remove', $item).'"',
                false,
            )
            ->assertSee('removeFromWishlist('.$itemId.')', false)
            ->assertSee('value="DELETE"', false);
    }

    /**
     * Save a fresh product to the current wishlist.
     *
     * @return array{0: int, 1: Product}
     */
    private function seedSavedProduct(
        int $stock = 10,
        int $priceInFils = 10_000,
    ): array {
        $product = $this->saveProduct($stock, $priceInFils);

        return [
            (int) WishlistItem::query()->latest('id')->value('id'),
            $product,
        ];
    }

    /**
     * Save a fresh product to the current wishlist and return it.
     */
    private function saveProduct(
        int $stock = 10,
        int $priceInFils = 10_000,
    ): Product {
        $product = Product::factory()
            ->active()
            ->create([
                'price_in_fils' => $priceInFils,
                'stock' => $stock,
                'reserved_stock' => 0,
                'low_stock_threshold' => 0,
            ]);

        app(WishlistService::class)->add($product);

        return $product;
    }
}
