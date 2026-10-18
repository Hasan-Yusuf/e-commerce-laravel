<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\FlashMessage;
use App\Enums\ToastType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CartTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A fixed, valid session id (40 alphanumeric chars) so guest requests within
     * a test share a cart. The test client does not replay the session cookie
     * automatically, so it is sent with every request instead.
     */
    private const string SESSION_ID = 'CartTestSession0123456789012345678901234';

    public function test_guest_can_add_a_product_to_the_cart(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas(
                FlashMessage::TITLE,
                __('carts.notify.added_title', ['name' => $product->name]),
            )
            ->assertSessionHas(
                FlashMessage::BODY,
                __('carts.notify.added_body', [
                    'count' => 2,
                    'name' => $product->name,
                ]),
            )
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price_in_fils' => 10_000,
        ]);

        $this->assertDatabaseHas('carts', ['session_id' => self::SESSION_ID]);
    }

    public function test_guest_cart_persists_across_requests(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('cart-item', false);
    }

    public function test_adding_the_same_product_twice_increments_the_line(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_quantity_is_clamped_to_available_stock_and_warns(): void
    {
        $product = $this->createProduct(['stock' => 3, 'reserved_stock' => 0]);

        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 5,
            ])
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_out_of_stock_product_is_not_added_and_warns(): void
    {
        $product = $this->createProduct(['stock' => 0, 'reserved_stock' => 0]);

        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $product = $this->createProduct(['activated_at' => null]);

        $this
            ->asGuest()
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_can_update_and_remove_a_line(): void
    {
        $product = $this->createProduct(['stock' => 10]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $item = CartItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->patch(route('cart.update', $item), ['quantity' => 4])
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        static::assertSame(4, $item->refresh()->quantity);

        $this
            ->asGuest()
            ->delete(route('cart.remove', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_update_clamps_the_quantity_to_stock_and_warns(): void
    {
        $product = $this->createProduct(['stock' => 2, 'reserved_stock' => 0]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $item = CartItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->patch(route('cart.update', $item), ['quantity' => 9])
            ->assertSessionHas(FlashMessage::TYPE, ToastType::WARNING->value);

        static::assertSame(2, $item->refresh()->quantity);
    }

    #[DataProvider('invalidQuantities')]
    public function test_update_rejects_invalid_quantities(
        int|string $quantity,
    ): void {
        $product = $this->createProduct(['stock' => 10]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $item = CartItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->patch(route('cart.update', $item), ['quantity' => $quantity])
            ->assertSessionHasErrors('quantity');
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public static function invalidQuantities(): array
    {
        return [[0], [-1], ['not-a-number'], [1_000]];
    }

    public function test_subtotal_and_total_are_shown_in_fils_based_currency(): void
    {
        $product = $this->createProduct([
            'price_in_fils' => 10_000,
            'stock' => 10,
        ]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('20 BHD')
            ->assertSee(__('carts.summary.subtotal'))
            ->assertSee(__('carts.summary.total'));
    }

    public function test_discounted_price_is_stored_as_the_snapshot(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $discount = Discount::factory()->percentage(25)->create();
        $discount->products()->attach($product);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'price_in_fils' => 7_500,
        ]);
    }

    public function test_price_change_notice_is_shown_when_the_snapshot_differs(): void
    {
        $product = $this->createProduct(['price_in_fils' => 10_000]);

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $product->update(['price_in_fils' => 12_000]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(
                __('carts.item.price_changed', [
                    'old' => '10 BHD',
                    'new' => '12 BHD',
                ]),
            );
    }

    public function test_soft_deleted_product_line_is_marked_and_removable(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $product->delete();

        $item = CartItem::query()->firstOrFail();

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('carts.item.unavailable_product'));

        $this
            ->asGuest()
            ->delete(route('cart.remove', $item))
            ->assertSessionHas(FlashMessage::TYPE, ToastType::SUCCESS->value);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_empty_cart_renders_the_empty_state_without_creating_a_cart(): void
    {
        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('carts.empty.title'))
            ->assertSee(__('carts.empty.action'));

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_expired_guest_cart_is_ignored_but_not_deleted_on_request(): void
    {
        $product = $this->createProduct();

        $expired = Cart::factory()
            ->expired()
            ->create([
                'session_id' => self::SESSION_ID,
            ]);

        // The cart page treats the expired cart as empty...
        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('carts.empty.title'));

        // ...but a request never deletes it; the scheduled job does.
        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', ['id' => $expired->id]);
        $this->assertDatabaseCount('carts', 2);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_inactive_product_line_is_marked_unavailable(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $product->update(['activated_at' => now()->addDay()]);

        $this
            ->asGuest()
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee(__('carts.item.inactive_product'));
    }

    public function test_a_line_from_another_cart_cannot_be_updated_or_removed(): void
    {
        $otherCart = Cart::factory()->create();
        $product = $this->createProduct();

        $item = CartItem::factory()
            ->forCart($otherCart)
            ->forProduct($product)
            ->create(['quantity' => 1, 'price_in_fils' => 10_000]);

        $this
            ->asGuest()
            ->patch(route('cart.update', $item), ['quantity' => 2])
            ->assertNotFound();

        $this->asGuest()->delete(route('cart.remove', $item))->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 1,
        ]);
    }

    public function test_header_badge_shows_the_cart_item_count(): void
    {
        $product = $this->createProduct();

        $this->asGuest()->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this
            ->asGuest()
            ->get(route('home'))
            ->assertOk()
            ->assertSee('site-header__cart-badge', false)
            ->assertSee(trans_choice('components.header.cart_count', 2));
    }

    public function test_product_page_add_to_cart_form_submits_product_and_quantity(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('product-detail__form', false)
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee('name="product_id"', false)
            ->assertSee('name="quantity"', false)
            ->assertSee('value="'.$product->id.'"', false);
    }

    public function test_product_cards_render_an_add_to_cart_form(): void
    {
        $product = $this->createProduct();

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee('name="product_id"', false)
            ->assertSee('value="'.$product->id.'"', false);
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
