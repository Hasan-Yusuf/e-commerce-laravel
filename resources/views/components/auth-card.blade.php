@props([
    'title',
    'subtitle' => null,
])

<section {{ $attributes->class(['auth-page', 'wrapper']) }}>
    <div class="card auth-card">
        <header class="page-header">
            <h1 class="page-header__title">{{ $title }}</h1>

            @if ($subtitle !== null)
                <p class="auth-card__subtitle">{{ $subtitle }}</p>
            @endif
        </header>

        {{ $slot }}

        @isset($footer)
            <footer class="auth-card__footer">{{ $footer }}</footer>
        @endisset
    </div>
</section>
