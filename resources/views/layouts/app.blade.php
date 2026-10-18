@props([
    'title' => null,
    'description' => null,
])

{{-- The page's own title and description are its own: they are passed through
     to the document rather than replaced with the shop's name here. --}}
<x-layouts::base :title="$title" :description="$description">
    {{-- Keyboard users can jump past the navigation band and into the page; the
         control is off-screen until it is focused. --}}
    <a class="site-skip-link" href="#main-content"> {{ __('components.a11y.skip_to_content') }} </a>

    <x-header />

    <x-auth-modal />

    <main class="site-main" id="main-content" tabindex="-1">
        {{-- Filled automatically from the current route; hidden when there is
             only the "home" entry (e.g. on the homepage). --}}
        <x-breadcrumbs />

        {{ $slot }}
    </main>

    <x-footer />

    {{-- Toast notifications (cart feedback, flashed status, errors). --}}
    <livewire:toasts />
</x-layouts::base>
