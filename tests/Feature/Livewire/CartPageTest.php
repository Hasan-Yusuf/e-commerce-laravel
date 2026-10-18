<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_renders_the_empty_state_when_the_cart_is_empty(): void
    {
        Livewire::test('cart-page')
            ->assertOk()
            ->assertSee(__('carts.empty.title'))
            ->assertSee(__('carts.empty.action'));
    }

    public function test_changing_the_quantity_updates_the_cart_and_totals_live(): void
    {
        [$itemId] = $this->seedLine(priceInFils: 10_000, quantity: 1);

        Livewire::test('cart-page')
            ->call('updateQuantity', $itemId, 3)
            ->assertHasNoErrors()
            ->assertSee('30 BHD')
            ->assertDispatched('cart-updated')
            ->assertDispatched('notify');

        static::assertSame(3, CartItem::find($itemId)->quantity);
    }

    public function test_quantity_is_clamped_to_stock_and_a_warning_toast_is_dispatched(): void
    {
        [$itemId] = $this->seedLine(stock: 3, quantity: 1);

        Livewire::test('cart-page')
            ->call('updateQuantity', $itemId, 99)
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated');

        static::assertSame(3, CartItem::find($itemId)->quantity);
    }

    public function test_an_invalid_quantity_is_rejected(): void
    {
        [$itemId] = $this->seedLine();

        Livewire::test('cart-page')
            ->call('updateQuantity', $itemId, 'not-a-number')
            ->assertHasErrors('quantity.'.$itemId);

        static::assertSame(1, CartItem::find($itemId)->quantity);
    }

    public function test_a_debounced_bound_quantity_update_is_persisted(): void
    {
        [$itemId] = $this->seedLine(quantity: 1);

        Livewire::test('cart-page')
            ->set('quantities.'.$itemId, 4)
            ->assertHasNoErrors()
            ->assertSet('quantities.'.$itemId, 4)
            ->assertDispatched('cart-updated');

        static::assertSame(4, CartItem::find($itemId)->quantity);
    }

    public function test_a_bound_quantity_is_mirrored_back_after_being_clamped_to_stock(): void
    {
        [$itemId] = $this->seedLine(stock: 3, quantity: 1);

        Livewire::test('cart-page')
            ->set('quantities.'.$itemId, 99)
            ->assertSet('quantities.'.$itemId, 3)
            ->assertDispatched('notify');

        static::assertSame(3, CartItem::find($itemId)->quantity);
    }

    public function test_a_quantity_below_one_is_rejected(): void
    {
        [$itemId] = $this->seedLine(quantity: 1);

        Livewire::test('cart-page')
            ->call('updateQuantity', $itemId, 0)
            ->assertHasErrors('quantity.'.$itemId);

        static::assertSame(1, CartItem::find($itemId)->quantity);
    }

    public function test_quantity_inputs_are_bound_to_a_debounced_model(): void
    {
        [$itemId] = $this->seedLine();

        Livewire::test('cart-page')
            ->assertSee(
                'wire:model.live.debounce.750ms="quantities.'.$itemId.'"',
                false,
            )
            // Steppers adjust the bound input instead of firing a request per click.
            ->assertSee('x-on:click="step(', false);
    }

    public function test_removing_a_line_deletes_it_and_empty_the_cart(): void
    {
        [$itemId, $product] = $this->seedLine();

        Livewire::test('cart-page')
            ->call('removeItem', $itemId)
            ->assertDontSee($product->name)
            ->assertSee(__('carts.empty.title'))
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_each_line_keeps_its_livewire_key(): void
    {
        [$itemId] = $this->seedLine();

        // The line is a Blade component now, so the key that lets Livewire morph
        // the right row in place has to survive that boundary.
        Livewire::test('cart-page')->assertSee(
            'wire:key="cart-item-'.$itemId.'"',
            false,
        );
    }

    public function test_the_table_header_names_the_columns(): void
    {
        $this->seedLine();

        Livewire::test('cart-page')
            ->assertSee('class="cart-items__header"', false)
            ->assertSee(__('carts.item.image'))
            ->assertSee(__('carts.item.product'))
            ->assertSee(__('carts.item.unit_price'))
            ->assertSee(__('carts.item.quantity'))
            ->assertSee(__('carts.item.line_total'))
            ->assertSee(__('carts.item.action'));
    }

    public function test_it_renders_icon_only_remove_and_wishlist_actions(): void
    {
        $this->seedLine();

        Livewire::test('cart-page')
            ->assertSee('cart-item__actions', false)
            // Both icon controls use the shared square, and the remove is the
            // same danger button as the wishlist card's remove.
            ->assertSee('btn btn--danger btn--icon', false)
            ->assertSee('btn btn--tertiary btn--icon', false)
            ->assertSee(__('carts.actions.move_to_wishlist'));
    }

    public function test_it_wires_a_move_to_wishlist_form_to_the_route(): void
    {
        [$itemId] = $this->seedLine();

        $item = CartItem::query()->findOrFail($itemId);

        Livewire::test('cart-page')
            ->assertSee('cart-item__move-form', false)
            ->assertSee(
                'action="'.route('cart.moveToWishlist', $item).'"',
                false,
            )
            ->assertSee('moveToWishlist('.$itemId.')', false)
            ->assertSee(__('carts.actions.move_to_wishlist'));
    }

    public function test_moving_a_line_to_the_wishlist_removes_it_from_the_cart(): void
    {
        [$itemId, $product] = $this->seedLine();

        Livewire::test('cart-page')
            ->call('moveToWishlist', $itemId)
            // The line leaves the cart...
            ->assertDontSee('cart-item', false)
            ->assertSee(__('carts.empty.title'))
            // ...and shows up under the wishlist section of the same page.
            ->assertSee('wishlist-card', false)
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated');

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
        ]);
    }

    public function test_moving_an_unavailable_line_to_the_wishlist_keeps_it_in_the_cart(): void
    {
        [$itemId, $product] = $this->seedLine();

        $product->delete();

        Livewire::test('cart-page')
            ->call('moveToWishlist', $itemId)
            ->assertDispatched('notify')
            ->assertNotDispatched('cart-updated');

        $this->assertDatabaseHas('cart_items', ['id' => $itemId]);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_it_renders_a_move_all_to_wishlist_button(): void
    {
        $this->seedLine();

        Livewire::test('cart-page')
            ->assertSee(
                'action="'.route('cart.moveAllToWishlist').'"',
                false,
            )
            ->assertSee('moveAllToWishlist', false)
            ->assertSee(__('carts.actions.move_all_to_wishlist'));
    }

    public function test_the_move_all_button_is_hidden_when_the_cart_is_empty(): void
    {
        Livewire::test('cart-page')->assertDontSee(
            __('carts.actions.move_all_to_wishlist'),
        );
    }

    public function test_moving_every_line_to_the_wishlist_empties_the_cart(): void
    {
        [, $first] = $this->seedLine();
        [, $second] = $this->seedLine();

        Livewire::test('cart-page')
            ->call('moveAllToWishlist')
            // The cart empties...
            ->assertSee(__('carts.empty.title'))
            // ...and both products show up under the wishlist section.
            ->assertSee($first->name)
            ->assertSee($second->name)
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated');

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('wishlist_items', 2);
    }

    public function test_moving_every_line_keeps_the_ones_whose_product_is_gone(): void
    {
        [$goneId, $gone] = $this->seedLine();
        [$availableId, $available] = $this->seedLine();

        $gone->delete();

        Livewire::test('cart-page')
            ->call('moveAllToWishlist')
            ->assertSet('quantities.'.$goneId, 1)
            // The moved line's binding goes with it, so the input cannot
            // resend a quantity for a line that is no longer there.
            ->assertSet('quantities.'.$availableId, null)
            ->assertDispatched('notify')
            ->assertDispatched('cart-updated');

        $this->assertDatabaseHas('cart_items', ['id' => $goneId]);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $available->id,
        ]);
        $this->assertDatabaseCount('wishlist_items', 1);
    }

    public function test_moving_every_line_leaves_the_cart_badge_alone_when_nothing_moved(): void
    {
        [, $gone] = $this->seedLine();

        $gone->delete();

        Livewire::test('cart-page')
            ->call('moveAllToWishlist')
            ->assertDispatched('notify')
            ->assertNotDispatched('cart-updated');

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_it_wires_no_js_fallback_forms_to_the_cart_routes(): void
    {
        [$itemId] = $this->seedLine();

        $item = CartItem::query()->findOrFail($itemId);

        Livewire::test('cart-page')
            // Quantity: the input posts to cart.update with method spoofing.
            ->assertSee('action="'.route('cart.update', $item).'"', false)
            ->assertSee('name="quantity"', false)
            ->assertSee('value="PATCH"', false)
            // Remove: posts to cart.remove as a DELETE.
            ->assertSee('action="'.route('cart.remove', $item).'"', false)
            ->assertSee('value="DELETE"', false)
            // Steppers are JS-only; the submit button is the no-JS path.
            ->assertSee('js-only', false)
            ->assertSee('no-js-only', false)
            ->assertSee(__('carts.actions.update'));
    }

    public function test_the_summary_sticks_below_the_navigation(): void
    {
        // The summary card belongs to the cart page (the checkout reuses it), so
        // its rule lives here. It pins once the lines and the summary sit beside
        // each other, and below the navigation from the breakpoint where that
        // navigation itself pins.
        $css = file_get_contents(resource_path('css/features/cart.css'));

        static::assertMatchesRegularExpression(
            '/@media \(min-width: 900px\) \{\s*\.cart-summary \{[\s\S]*?position: sticky;\s*inset-block-start: var\(--_sticky-offset\);/',
            $css,
        );
        static::assertMatchesRegularExpression(
            '/@media \(min-width: 1161px\) \{\s*\.cart-summary \{\s*--_sticky-offset: calc\(\s*var\(--size-sticky-nav\) \+ var\(--space-400\)\s*\);/',
            $css,
        );
    }

    public function test_the_coupon_error_spans_under_the_input_and_its_button(): void
    {
        // The coupon field is dissolved into the form's grid, so its error can
        // close across the full width under both the input and the Apply button
        // instead of stopping at the input's column.
        $css = file_get_contents(resource_path('css/features/cart.css'));

        static::assertMatchesRegularExpression(
            '/\.cart-summary__coupon > \.form-field \{[\s\S]*?display: contents;/',
            $css,
        );
        static::assertMatchesRegularExpression(
            '/\.cart-summary__coupon > \.form-field > \.form-error \{\s*grid-area: 3 \/ 1 \/ 4 \/ -1;/',
            $css,
        );
    }

    /**
     * Create a fresh product and add it to the current (test) cart.
     *
     * @return array{0: int, 1: Product}
     */
    private function seedLine(
        int $priceInFils = 10_000,
        int $stock = 10,
        int $quantity = 1,
    ): array {
        $product = Product::factory()
            ->active()
            ->create([
                'price_in_fils' => $priceInFils,
                'stock' => $stock,
                'reserved_stock' => 0,
                'low_stock_threshold' => 0,
            ]);

        app(CartService::class)->add($product, $quantity);

        return [(int) CartItem::query()->latest('id')->value('id'), $product];
    }
}
