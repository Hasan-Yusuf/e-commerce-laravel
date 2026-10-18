<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PruneExpiredCartsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_deletes_only_expired_carts_and_their_items(): void
    {
        $product = Product::factory()
            ->active()
            ->create([
                'stock' => 5,
                'reserved_stock' => 0,
            ]);

        $expired = Cart::factory()->expired()->create();
        CartItem::factory()
            ->forCart($expired)
            ->forProduct($product)
            ->create();

        $active = Cart::factory()->create();
        CartItem::factory()
            ->forCart($active)
            ->forProduct($product)
            ->create();

        $this->artisan('carts:prune')->assertSuccessful();

        $this->assertDatabaseMissing('carts', ['id' => $expired->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $expired->id]);

        $this->assertDatabaseHas('carts', ['id' => $active->id]);
        $this->assertDatabaseHas('cart_items', ['cart_id' => $active->id]);
    }
}
