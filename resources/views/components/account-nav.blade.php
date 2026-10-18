@php
    // The sections of the account area, in the order they are offered. The
    // wishlist has no page here: it is rendered under the cart, which the
    // header's cart button already reaches.
    $sections = [
        [
            'label' => __('account.nav.overview'),
            'url' => route('account.index'),
            'active' => request()->routeIs('account.index'),
        ],
        [
            'label' => __('account.nav.orders'),
            'url' => route('orders.index'),
            // The history and one order out of it are the same section.
            'active' => request()->routeIs('orders.index', 'orders.show'),
        ],
        [
            'label' => __('account.nav.details'),
            'url' => route('account.edit'),
            'active' => request()->routeIs('account.edit'),
        ],
    ];
@endphp

<nav class="account-nav" aria-label="{{ __('account.nav.label') }}">
    <ul class="account-nav__list">
        @foreach ($sections as $section)
            <li class="account-nav__item">
                <a
                    class="account-nav__link{{ $section['active'] ? ' account-nav__link--active' : '' }}"
                    href="{{ $section['url'] }}"
                    @if ($section['active']) aria-current="page" @endif
                >
                    {{ $section['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
