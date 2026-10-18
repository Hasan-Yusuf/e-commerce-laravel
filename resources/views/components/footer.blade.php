@php
    // The shop's own details, written down once in config/store.php so the
    // footer and the contact page cannot drift apart.
    $phone = config('store.contact.phone');
    $phoneHref = config('store.contact.phone_href');
    $instagramUrl = config('store.contact.instagram_url');
    $tiktokUrl = config('store.contact.tiktok_url');
@endphp

<footer class="site-footer">
    <div class="site-footer__inner wrapper wrapper--wide">
        <x-button
            variant="secondary"
            class="site-footer__action"
            :href="$phoneHref"
            :aria-label="__('components.footer.call', ['number' => $phone])"
        >
            <x-icons.phone-icon :size="18" aria-hidden="true" />

            <span>{{ $phone }}</span>
        </x-button>

        <div class="site-footer__links">
            <x-button variant="secondary" class="site-footer__action" :href="route('contact.index')">
                <x-icons.info-icon :size="18" aria-hidden="true" />

                <span>{{ __('components.footer.contact') }}</span>
            </x-button>

            <x-button
                variant="secondary"
                class="site-footer__action"
                :href="$instagramUrl"
                target="_blank"
                rel="noopener noreferrer"
            >
                <x-icons.instagram-icon :size="18" aria-hidden="true" />

                <span>{{ __('components.footer.instagram') }}</span>

                <span class="visually-hidden"> {{ __('components.footer.opens_in_new_tab') }} </span>
            </x-button>

            <x-button
                variant="secondary"
                class="site-footer__action"
                :href="$tiktokUrl"
                target="_blank"
                rel="noopener noreferrer"
            >
                <x-icons.tiktok-icon :size="18" aria-hidden="true" />

                <span>{{ __('components.footer.tiktok') }}</span>

                <span class="visually-hidden"> {{ __('components.footer.opens_in_new_tab') }} </span>
            </x-button>
        </div>
    </div>
</footer>
