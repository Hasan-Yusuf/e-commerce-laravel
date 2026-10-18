{{-- The band the page gives one row of its grid: the top bar and the navigation
     under it travel together, so nothing is spaced between them. The navigation
     lives inside this box rather than inside the <header> element itself, which
     is what lets it stick to the top of the viewport for the whole page — a
     sticky element only travels inside its parent's box (see .site-header-band
     in components/layout.css, which reaches over the page's row for it). --}}
<div class="site-header-band">
    <header class="site-header">
        {{-- Top bar: logo, user actions, burger --}}
        <div class="site-header__top wrapper wrapper--wide">
            <a href="{{ route('home') }}" class="text-no-decor site-header__logo-link">
                <span class="site-header__logo">{{ __('components.header.logo') }}</span>
            </a>

            <x-header-actions />

            {{-- Burger: opens the off-canvas drawer on mobile --}}
            <x-button variant="bare" class="site-header__burger" aria-expanded="false" aria-controls="site-nav">
                <span class="visually-hidden">{{ __('components.header.menu_toggle') }}</span>
                <x-icons.menu-icon class="site-header__burger-icon" aria-hidden="true" />
                <x-icons.close-icon class="site-header__burger-icon" aria-hidden="true" />
            </x-button>
        </div>

        @if (request()->routeIs('home'))
            <div class="site-header__search-row">
                <div class="wrapper wrapper--wide">
                    <x-header-search id="search-mobile" />
                </div>
            </div>
        @endif

        <div class="site-header__overlay"></div>
    </header>

    {{-- Main navigation: single-column disclosure submenus, with the compact
         search on the end of the bar. --}}
    <nav class="site-header__nav" id="site-nav" aria-label="{{ __('components.header.navigation') }}">
        {{-- Mobile-only close control, shown above the menu: the open drawer
             covers the top bar's burger button. --}}
        <div class="site-header__nav-top">
            <x-button
                variant="bare"
                class="site-header__nav-close"
                data-nav-close
                :aria-label="__('components.header.menu_close')"
            >
                <x-icons.close-icon class="site-header__nav-close-icon" aria-hidden="true" />
            </x-button>
        </div>

        <div class="site-header__nav-bar wrapper wrapper--wide">
            <ul class="site-header__nav-list">
                @foreach ($categories as $category)
                    <li class="site-header__nav-item">
                        <a
                            @class([
                                'site-header__nav-link',
                                'site-header__nav-link--active' => $category->id === $activeAncestorId,
                            ])
                            href="{{ route('category.show', $category->slug) }}"
                        >
                            <span class="site-header__nav-link-label">{{ $category->name }}</span>
                        </a>

                        @if ($category->children->isNotEmpty())
                            <x-button
                                variant="bare"
                                class="site-header__nav-toggle"
                                aria-expanded="false"
                                :aria-controls="'submenu-'.$category->id"
                                :aria-label="__('components.header.submenu_toggle', ['category' => $category->name])"
                            >
                                <x-icons.chevron-down-icon class="site-header__nav-toggle-icon" aria-hidden="true" />
                            </x-button>

                            {{-- Two-level submenu: child links only --}}
                            <ul class="site-header__submenu" id="submenu-{{ $category->id }}" hidden>
                                @foreach ($category->children as $childCategory)
                                    <li class="site-header__submenu-item">
                                        <a
                                            @class([
                                                'site-header__submenu-link',
                                                'site-header__submenu-link--active' => $childCategory->id === $activeCategoryId,
                                            ])
                                            href="{{ route('category.show', $childCategory->slug) }}"
                                        >
                                            {{ $childCategory->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Compact variant so the search fits beside the categories. --}}
            <x-header-search id="search" class="site-header__search--nav" />
        </div>

        <x-header-actions variant="drawer" />
    </nav>
</div>
