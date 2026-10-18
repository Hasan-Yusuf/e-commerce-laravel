<?php

use App\Services\Cart\CartService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The header cart item-count badge. Listens for `cart-updated` (dispatched by
 * the cart page) so it refreshes without a full page reload.
 */
new class extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-rendering is enough: the computed count is resolved per request.
    }

    #[Computed]
    public function count(): int
    {
        return app(CartService::class)->itemCount();
    }
};
?>

<span class="site-header__cart-count">
    @if ($this->count > 0)
        <span class="site-header__cart-badge" aria-hidden="true"> {{ $this->count > 99 ? '99+' : $this->count }} </span>
        <span class="visually-hidden"> {{ trans_choice('components.header.cart_count', $this->count) }} </span>
    @endif
</span>
