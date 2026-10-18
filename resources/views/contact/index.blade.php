@php
    $phone = config('store.contact.phone');
    $phoneHref = config('store.contact.phone_href');
    $instagramUrl = config('store.contact.instagram_url');
    $tiktokUrl = config('store.contact.tiktok_url');
@endphp

<x-layouts::app :title="__('contact.title')">
    @pushOnce('styles')
        @vite('resources/css/features/contact.css')
    @endPushOnce

    <section class="content-section">
        <div class="contact wrapper">
            <header class="contact__header page-header page-header--spaced">
                <h1 class="page-header__title">{{ __('contact.title') }}</h1>

                <p class="page-header__subtitle">{{ __('contact.subtitle') }}</p>
            </header>

            <div class="contact-layout">
                <section class="card contact-layout__details">
                    <h2 class="contact-card__title">{{ __('contact.info.title') }}</h2>

                    <ul class="contact-details">
                        <li>
                            <a class="contact-details__link" href="{{ $phoneHref }}">
                                <x-icons.phone-icon :size="18" aria-hidden="true" />

                                <span>{{ __('contact.info.phone') }}: {{ $phone }}</span>
                            </a>
                        </li>

                        <li>
                            <a
                                class="contact-details__link"
                                href="{{ $instagramUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <x-icons.instagram-icon :size="18" aria-hidden="true" />

                                <span>{{ __('contact.info.instagram') }}</span>

                                <span class="visually-hidden"> {{ __('components.footer.opens_in_new_tab') }} </span>
                            </a>
                        </li>

                        <li>
                            <a
                                class="contact-details__link"
                                href="{{ $tiktokUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <x-icons.tiktok-icon :size="18" aria-hidden="true" />

                                <span>{{ __('contact.info.tiktok') }}</span>

                                <span class="visually-hidden"> {{ __('components.footer.opens_in_new_tab') }} </span>
                            </a>
                        </li>
                    </ul>

                    <p class="contact-details__note">{{ __('contact.info.note') }}</p>
                </section>

                <section class="card contact-layout__form">
                    <h2 class="contact-card__title">{{ __('contact.form.title') }}</h2>

                    @if ($message = \App\DTOs\FlashMessage::fromSession())
                        <x-alert type="success" :title="__('contact.form.success_title')" :message="$message->title" />
                    @endif

                    <form class="contact-form form" method="POST" action="{{ route('contact.store') }}" data-submit-once>
                        @csrf

                        <x-inputs.text name="name" :label="__('contact.form.name')" autocomplete="name" required />

                        <x-inputs.text
                            name="email"
                            type="email"
                            :label="__('contact.form.email')"
                            autocomplete="email"
                            required
                        />

                        <x-inputs.text name="subject" :label="__('contact.form.subject')" required />

                        <x-inputs.textarea name="message" rows="6" :label="__('contact.form.message')" required />

                        {{-- The honeypot: no shopper can see it, reach it with the
                             keyboard, or read it with a screen reader, so whatever
                             fills it has answered every field it could find rather
                             than this one. --}}
                        <div class="visually-hidden" aria-hidden="true">
                            <label for="field-website">{{ __('contact.form.website') }}</label>

                            <input type="text" id="field-website" name="website" tabindex="-1" autocomplete="off" />
                        </div>

                        <div class="form-actions">
                            <x-button type="submit" block> {{ __('contact.form.submit') }} </x-button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </section>
</x-layouts::app>
