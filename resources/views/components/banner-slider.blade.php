@props([
    'banners' => collect(),
])

@if ($banners->isNotEmpty())
    {{-- The hero is a scroller like the sections are: one banner at a time, and
         the same pair of buttons beside it. --}}
    <section
        class="banner-slider"
        data-scroller-group
        aria-roledescription="carousel"
        aria-label="{{ __('homepage.hero.label') }}"
    >
        <x-scroller-nav variant="on-action" />

        <div class="wrapper">
            <div class="banner-slider__track" data-scroller>
                @foreach ($banners as $banner)
                    @php($image = \App\Services\ImageService::forMedia($banner->getFirstMedia('image')))

                    {{-- The image is the backdrop, the caption sits on it: the
                         gradient the caption needs to stay legible rides on the
                         slide only when there is one. --}}
                    <x-banner-slide :banner="$banner" @class(['banner-slider__slide--with-image' => $image !== null])>
                        {{-- The hero is the image the page is judged on: the first
                             slide is fetched with the document and asked for
                             first, while the slides nobody has stepped to are
                             left until they are stepped to. A slide spans the
                             page's measure, which is what the browser sizes its
                             choice of variant by. --}}
                        <x-media-image
                            :image="$image"
                            class="banner-slider__image"
                            :alt="$banner->title"
                            sizes="(min-width: 1200px) 1168px, calc(100vw - 32px)"
                            :priority="$loop->first"
                        />

                        <p class="banner-slider__title">{{ $banner->title }}</p>
                    </x-banner-slide>
                @endforeach
            </div>
        </div>
    </section>
@endif
