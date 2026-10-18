<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\Product;
use App\Services\Wishlist\WishlistService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The add-to-cart / wishlist controls on a product card and on the product
 * page. They are Livewire so a click updates them (and the header badge) in
 * place instead of reloading the page, which used to scroll back to the top.
 */
class ProductActionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_card_controls_keep_their_prg_fallbacks(): void
    {
        $product = $this->createProduct();

        Livewire::test('product-actions', ['productId' => $product->id])
            ->assertSee('product-card__actions', false)
            // Add to cart: real form + Livewire handler.
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee('wire:submit="addToCart"', false)
            ->assertSee('name="product_id"', false)
            ->assertSee('value="'.$product->id.'"', false)
            // Wishlist toggle: real form + Livewire handler.
            ->assertSee('action="'.route('wishlist.add').'"', false)
            ->assertSee('wire:submit="toggleWishlist"', false);
    }

    public function test_the_detail_variant_renders_the_quantity_and_the_labeled_toggle(): void
    {
        $product = $this->createProduct();

        Livewire::test('product-actions', [
            'productId' => $product->id,
            'variant' => 'detail',
        ])
            ->assertSee('product-detail__actions-group', false)
            ->assertSee('product-detail__form', false)
            ->assertSee('wire:model="quantity"', false)
            ->assertSee('product-detail__wishlist', false)
            ->assertSee('wishlist-button--labeled', false)
            ->assertSee(__('homepage.add_to_cart'));
    }

    public function test_adding_to_the_cart_updates_the_badge_and_shows_a_toast(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        Livewire::test('product-actions', ['productId' => $product->id])
            ->call('addToCart')
            ->assertDispatched('cart-updated')
            ->assertDispatched('notify')
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'price_in_fils' => 10_000,
        ]);
    }

    public function test_the_detail_quantity_is_used_and_clamped_to_stock(): void
    {
        $product = $this->createProduct(['stock' => 3, 'reserved_stock' => 0]);

        Livewire::test('product-actions', [
            'productId' => $product->id,
            'variant' => 'detail',
        ])
            ->set('quantity', 5)
            ->call('addToCart')
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_an_invalid_quantity_is_rejected(): void
    {
        $product = $this->createProduct();

        Livewire::test('product-actions', [
            'productId' => $product->id,
            'variant' => 'detail',
        ])
            ->set('quantity', 'not-a-number')
            ->call('addToCart')
            ->assertHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_an_out_of_stock_product_cannot_be_added(): void
    {
        $product = $this->createProduct(['stock' => 0, 'reserved_stock' => 0]);

        Livewire::test('product-actions', ['productId' => $product->id])
            ->call('addToCart')
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_the_heart_saves_the_product_and_flips(): void
    {
        $product = $this->createProduct();

        Livewire::test('product-actions', ['productId' => $product->id])
            ->assertSee(__('wishlist.actions.add'))
            ->assertDontSee('wishlist-button--active', false)
            ->call('toggleWishlist')
            ->assertDispatched('notify')
            ->assertSee(__('wishlist.actions.remove'))
            ->assertSee('wishlist-button--active', false);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
        ]);
    }

    public function test_the_heart_removes_an_already_saved_product(): void
    {
        $product = $this->createProduct();

        app(WishlistService::class)->add($product);

        Livewire::test('product-actions', ['productId' => $product->id])
            ->assertSee('wishlist-button--active', false)
            ->call('toggleWishlist')
            ->assertSee(__('wishlist.actions.add'))
            ->assertDontSee('wishlist-button--active', false);

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_a_product_that_no_longer_exists_renders_no_controls(): void
    {
        $product = $this->createProduct();
        $productId = $product->id;

        $product->delete();

        Livewire::test('product-actions', ['productId' => $productId])
            ->assertOk()
            ->assertDontSee(__('homepage.add_to_cart'))
            ->assertDontSee('wire:submit="addToCart"', false);
    }

    public function test_product_cards_are_served_with_livewire_action_controls(): void
    {
        $this->createProduct();

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('product-card__actions', false)
            ->assertSee('wire:submit="addToCart"', false)
            ->assertSee('wire:submit="toggleWishlist"', false);
    }

    public function test_the_product_page_renders_its_actions_as_one_component(): void
    {
        $product = $this->createProduct();

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('product-detail__actions-group', false)
            ->assertSee('wire:model="quantity"', false)
            ->assertSee('wire:submit="addToCart"', false)
            ->assertSee('wire:submit="toggleWishlist"', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        return Product::factory()
            ->active()
            ->create(
                array_merge(
                    [
                        'stock' => 10,
                        'reserved_stock' => 0,
                        'low_stock_threshold' => 0,
                    ],
                    $attributes,
                ),
            );
    }
}
