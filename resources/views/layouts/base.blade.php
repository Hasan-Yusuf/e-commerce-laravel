@props([
    'title' => null,
    'description' => null,
])

@php
    $locale = app()->currentLocale();
    $dir = config('app.locale_dir', config("locales.$locale.dir", 'ltr'));
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="no-js">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <script>
        document.documentElement.classList.replace('no-js', 'js');
    </script>

    @vite(['resources/css/my-app.css', 'resources/js/app.js'])

    @stack('styles')

    @livewireStyles

    <title>{{ $title ?? __('homepage.title') }}</title>

    {{-- Every page describes itself to a search engine; a page that has nothing
         better to say falls back to the shop's own description rather than to
         none, which is what an empty meta description amounts to. --}}
    <meta name="description" content="{{ filled($description) ? $description : __('meta.default_description') }}" />
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
