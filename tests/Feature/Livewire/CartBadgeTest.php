<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartBadgeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_renders_nothing_when_the_cart_is_empty(): void
    {
        Livewire::test('cart-badge')
            ->assertDontSee('site-header__cart-badge', false);
    }

    public function test_it_shows_the_total_item_count(): void
    {
        $this->seedLine(quantity: 2);

        Livewire::test('cart-badge')
            ->assertSee('site-header__cart-badge', false)
            ->assertSee(trans_choice('components.header.cart_count', 2));
    }

    public function test_it_refreshes_when_the_cart_updated_event_is_dispatched(): void
    {
        $component = Livewire::test('cart-badge');

        $component->assertDontSee('site-header__cart-badge', false);

        $this->seedLine(quantity: 3);

        $component
            ->dispatch('cart-updated')
            ->assertSee('site-header__cart-badge', false)
            ->assertSee(trans_choice('components.header.cart_count', 3));
    }

    private function seedLine(int $quantity): void
    {
        $product = Product::factory()
            ->active()
            ->create([
                'price_in_fils' => 1_000,
                'stock' => 10,
                'reserved_stock' => 0,
                'low_stock_threshold' => 0,
            ]);

        app(CartService::class)->add($product, $quantity);
    }
}
