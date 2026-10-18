<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\FlashMessage;
use App\Enums\ToastType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so guest requests within
     * a test share a wishlist. The test client does not replay the session
     * cookie automatically, so it is sent with every request instead.
     */
    private const string SESSION_ID = 'WishlistTestSession012345678901234567890';

    public function test_guest_can_save_a_product_to_the_wishlist(): void
    {
        $product = $this->createProduct();

        $this
            ->asGuest()
            ->post(route('wishlist.add'), ['product_id' => $product->id])
            ->assertRedirect()
            ->assertSessionHas(
                FlashMessage::TITLE,
                __('wishlist.notify.added_title', ['name' => $product->name]),
            )
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'session_id' => self::SESSION_ID,
            'user_id' => null,
        ]);
    }

    public function test_saving_the_same_product_twice_is_a_no_op(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $this
            ->asGuest()
            ->post(route('wishlist.add'), ['product_id' => $product->id])
            ->assertRedirect()
            ->assertSessionHas(FlashMessage::TYPE, ToastType::INFO->value);

        $this->assertDatabaseCount('wishlist_items', 1);
    }

    public function test_guest_wishlist_persists_across_requests(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('id="wishlist"', false)
            ->assertSee($product->name)
            ->assertSee('wishlist-card', false);
    }

    public function test_guest_can_remove_a_saved_item(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $item = WishlistItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->delete(route('wishlist.remove', $item))
            ->assertRedirect()
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_an_empty_wishlist_explains_itself_on_the_cart_page(): void
    {
        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('wishlist.title'))
            ->assertSee(__('wishlist.empty.message'));

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    #[DataProvider('invalidProductIds')]
    public function test_saving_rejects_invalid_products(
        int|string|null $productId,
    ): void {
        $this
            ->asGuest()
            ->post(route('wishlist.add'), ['product_id' => $productId])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    /**
     * @return array<int, array<int, int|string|null>>
     */
    public static function invalidProductIds(): array
    {
        return [[null], [0], [999_999], ['not-a-number']];
    }

    public function test_inactive_product_cannot_be_saved(): void
    {
        $product = $this->createProduct(['activated_at' => now()->addDay()]);

        $this
            ->asGuest()
            ->post(route('wishlist.add'), ['product_id' => $product->id])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_soft_deleted_product_cannot_be_saved(): void
    {
        $product = $this->createProduct();
        $product->delete();

        $this
            ->asGuest()
            ->post(route('wishlist.add'), ['product_id' => $product->id])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_moving_a_saved_item_to_the_cart_uses_quantity_one_and_drops_it(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $item = WishlistItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->post(route('wishlist.moveToCart', $item))
            ->assertRedirect()
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('wishlist_items', 0);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'price_in_fils' => 10_000,
        ]);
    }

    public function test_moving_a_saved_item_to_the_cart_merges_with_an_existing_line(): void
    {
        $product = $this->createProduct(['stock' => 10]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $item = WishlistItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->post(route('wishlist.moveToCart', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('wishlist_items', 0);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_moving_a_saved_item_clamps_to_stock_and_warns(): void
    {
        $product = $this->createProduct(['stock' => 1, 'reserved_stock' => 0]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $item = WishlistItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->post(route('wishlist.moveToCart', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseCount('wishlist_items', 0);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_an_out_of_stock_saved_item_stays_on_the_wishlist(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $item = WishlistItem::query()->firstOrFail();

        $product->update(['stock' => 0, 'reserved_stock' => 0]);

        $this
            ->asGuest()
            ->post(route('wishlist.moveToCart', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('wishlist_items', ['id' => $item->id]);
    }

    public function test_a_gone_product_stays_on_the_wishlist_and_can_be_removed(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $product->delete();

        $item = WishlistItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('wishlist.item.unavailable_product'))
            ->assertSee('wishlist-card--gone', false);

        $this
            ->asGuest()
            ->post(route('wishlist.moveToCart', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseHas('wishlist_items', ['id' => $item->id]);

        $this
            ->asGuest()
            ->delete(route('wishlist.remove', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_moving_a_cart_line_to_the_wishlist_drops_the_line(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = CartItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->post(route('cart.moveToWishlist', $item))
            ->assertRedirect()
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'session_id' => self::SESSION_ID,
        ]);
    }

    public function test_moving_a_cart_line_already_wishlisted_only_drops_the_line(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);
        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $item = CartItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->post(route('cart.moveToWishlist', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::INFO->value);

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('wishlist_items', 1);
    }

    public function test_moving_a_cart_line_with_a_soft_deleted_product_keeps_it(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $item = CartItem::query()->firstOrFail();

        $product->delete();

        $this
            ->asGuest()
            ->post(route('cart.moveToWishlist', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_moving_the_whole_cart_to_the_wishlist_empties_it(): void
    {
        $first = $this->createProduct();
        $second = $this->createProduct();

        foreach ([$first, $second] as $product) {
            $this->asGuest()->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        }

        $this
            ->asGuest()
            ->post(route('cart.moveAllToWishlist'))
            ->assertRedirect()
            ->assertSessionHas(
                FlashMessage::TITLE,
                __('wishlist.notify.moved_all_from_cart_title'),
            )
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('wishlist_items', 2);
    }

    public function test_moving_the_whole_cart_keeps_a_line_whose_product_is_gone(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $item = CartItem::query()->firstOrFail();

        $product->delete();

        $this
            ->asGuest()
            ->post(route('cart.moveAllToWishlist'))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_moving_the_whole_cart_reports_an_empty_cart(): void
    {
        $this
            ->asGuest()
            ->post(route('cart.moveAllToWishlist'))
            ->assertSessionHas(
                FlashMessage::TITLE,
                __('wishlist.notify.nothing_to_move_title'),
            )
            ->assertSessionHas(FlashMessage::TYPE, ToastType::INFO->value);
    }

    public function test_moving_the_whole_wishlist_to_the_cart_empties_it(): void
    {
        $first = $this->createProduct();
        $second = $this->createProduct();

        foreach ([$first, $second] as $product) {
            $this->asGuest()->post(route('wishlist.add'), [
                'product_id' => $product->id,
            ]);
        }

        $this
            ->asGuest()
            ->post(route('wishlist.moveAllToCart'))
            ->assertRedirect()
            ->assertSessionHas(
                FlashMessage::TITLE,
                __('wishlist.notify.moved_all_to_cart_title'),
            )
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('wishlist_items', 0);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_moving_the_whole_wishlist_keeps_an_unavailable_entry(): void
    {
        $available = $this->createProduct();
        $soldOut = $this->createProduct(['stock' => 0, 'reserved_stock' => 0]);

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $available->id,
        ]);
        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $soldOut->id,
        ]);

        $saved = WishlistItem::query()
            ->where('product_id', $soldOut->id)
            ->firstOrFail();

        $this
            ->asGuest()
            ->post(route('wishlist.moveAllToCart'))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseHas('wishlist_items', ['id' => $saved->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $available->id,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $soldOut->id,
        ]);
    }

    public function test_the_cart_page_renders_both_bulk_move_buttons(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $this->createProduct()->id,
        ]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(
                'action="'.route('cart.moveAllToWishlist').'"',
                false,
            )
            ->assertSee(__('carts.actions.move_all_to_wishlist'))
            ->assertSee(
                'action="'.route('wishlist.moveAllToCart').'"',
                false,
            )
            ->assertSee(__('wishlist.actions.move_all_to_cart'));
    }

    public function test_a_wishlist_item_owned_by_another_session_is_not_addressable(): void
    {
        $product = $this->createProduct();

        $foreign = WishlistItem::factory()
            ->forSession('AnotherWishlistSession0123456789012345678')
            ->forProduct($product)
            ->create();

        $this
            ->asGuest()
            ->delete(route('wishlist.remove', $foreign))
            ->assertNotFound();

        $this
            ->asGuest()
            ->post(route('wishlist.moveToCart', $foreign))
            ->assertNotFound();

        $this->assertDatabaseHas('wishlist_items', ['id' => $foreign->id]);
    }

    public function test_a_wishlist_item_owned_by_another_user_is_not_addressable(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $item = WishlistItem::factory()
            ->forUser($owner)
            ->forProduct($this->createProduct())
            ->create();

        $this
            ->actingAs($intruder)
            ->delete(route('wishlist.remove', $item))
            ->assertNotFound();

        $this->assertDatabaseHas('wishlist_items', ['id' => $item->id]);
    }

    public function test_a_cart_line_owned_by_another_cart_is_not_addressable(): void
    {
        $otherCart = Cart::factory()->create();
        $product = $this->createProduct();

        $item = CartItem::factory()
            ->forCart($otherCart)
            ->forProduct($product)
            ->create(['quantity' => 1, 'price_in_fils' => 10_000]);

        $this
            ->asGuest()
            ->post(route('cart.moveToWishlist', $item))
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_guest_and_user_wishlists_are_isolated(): void
    {
        $product = $this->createProduct();
        $user = User::factory()->create();

        WishlistItem::factory()->forUser($user)->forProduct($product)->create();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseCount('wishlist_items', 2);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'session_id' => self::SESSION_ID,
        ]);
    }

    public function test_guest_does_not_see_another_sessions_wishlist(): void
    {
        WishlistItem::factory()
            ->forSession('AnotherWishlistSession0123456789012345678')
            ->forProduct($this->createProduct())
            ->create();

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('wishlist.empty.message'));
    }

    public function test_signed_in_shoppers_wishlist_is_stored_against_their_account(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this
            ->actingAs($user)
            ->post(route('wishlist.add'), ['product_id' => $product->id])
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseHas('wishlist_items', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_the_wishlist_and_remove_controls_share_one_icon_button(): void
    {
        // One square (`--size-icon-button`) applied by the shared `btn--icon`
        // size, so the heart and both removes can never drift apart again.
        static::assertStringContainsString(
            '--size-icon-button: 2.5rem;',
            file_get_contents(resource_path('css/tokens/design-tokens.css')),
        );

        static::assertMatchesRegularExpression(
            '/\.btn--icon \{[\s\S]*?inline-size: var\(--size-icon-button\);[\s\S]*?block-size: var\(--size-icon-button\);/',
            file_get_contents(resource_path('css/components/button.css')),
        );

        // The heart on a product card…
        $product = $this->createProduct();

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('btn btn--tertiary btn--icon', false);

        // …and the cart line's remove + heart and the wishlist card's remove.
        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('btn btn--danger btn--icon', false)
            ->assertSee('btn btn--tertiary btn--icon', false);
    }

    public function test_the_header_has_no_wishlist_shortcut(): void
    {
        // The wishlist is reached through the cart page, so the header keeps a
        // single cart action and no heart linking to it.
        $this
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('site-header__wishlist', false)
            ->assertSee('href="'.route('cart.index').'"', false);
    }

    public function test_product_cards_render_a_wishlist_form(): void
    {
        $product = $this->createProduct();

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('action="'.route('wishlist.add').'"', false)
            ->assertSee('name="product_id"', false)
            ->assertSee('value="'.$product->id.'"', false);
    }

    public function test_product_cards_show_the_saved_state_for_wishlisted_products(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $item = WishlistItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('wishlist-button--active', false)
            ->assertSee(
                'action="'.route('wishlist.remove', $item).'"',
                false,
            )
            ->assertSee('value="DELETE"', false);
    }

    public function test_product_page_renders_a_wishlist_form(): void
    {
        $product = $this->createProduct();

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('product-detail__wishlist', false)
            ->assertSee('action="'.route('wishlist.add').'"', false)
            ->assertSee(__('wishlist.actions.add'));
    }

    public function test_the_cart_page_renders_the_wishlist_actions(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $product->id,
        ]);

        $item = WishlistItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(
                'action="'.route('wishlist.moveToCart', $item).'"',
                false,
            )
            ->assertSee(
                'action="'.route('wishlist.remove', $item).'"',
                false,
            )
            ->assertSee('value="DELETE"', false)
            ->assertSee(__('wishlist.actions.move_to_cart'))
            ->assertSee(__('wishlist.actions.remove'));
    }

    public function test_the_wishlist_section_counts_the_saved_items(): void
    {
        $first = $this->createProduct();
        $second = $this->createProduct();

        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $first->id,
        ]);
        $this->asGuest()->post(route('wishlist.add'), [
            'product_id' => $second->id,
        ]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(trans_choice('wishlist.count', 2));
    }

    private function asGuest(): static
    {
        return $this->withCookie(config('session.cookie'), self::SESSION_ID);
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
