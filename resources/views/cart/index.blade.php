<x-layouts::app :title="__('carts.title')">
    @pushOnce('styles')
        @vite([
            'resources/css/features/cart.css',
            'resources/css/features/wishlist.css',
        ])
    @endPushOnce

    <section class="content-section">
        <livewire:cart-page />
    </section>
</x-layouts::app>
