@php
    $phone = $user->phone;

    // The countries the phone's calling code and number length come from — the
    // same list the address form below offers.
    $countries = \App\Models\Country::query()->orderBy('name_en')->get();
@endphp

<x-layouts::app :title="__('account.profile.title')">
    @pushOnce('styles')
        @vite('resources/css/features/account.css')
    @endPushOnce

    <section class="content-section">
        <div class="account-layout wrapper">
            <x-account-nav />

            <div class="account-sections">
                <header class="page-header">
                    <h1 class="page-header__title">{{ __('account.profile.title') }}</h1>

                    <p class="page-header__subtitle">{{ __('account.profile.subtitle') }}</p>
                </header>

                {{-- Who the shopper is, and how they are reached. The account opens
                     as what it holds: every form here is read-only until the
                     shopper says they want to change it (see account-form.js). --}}
                <form class="card account-form form" method="POST" action="{{ route('account.update') }}" data-account-form>
                    @csrf
                    @method('PATCH')

                    <div class="account-form__header">
                        <h2 class="account-panel__title">{{ __('account.profile.details.title') }}</h2>

                        <x-button
                            variant="tertiary"
                            type="button"
                            class="account-form__toggle js-only"
                            data-account-form-toggle
                        >
                            {{ __('account.profile.edit') }}
                        </x-button>
                    </div>

                    <x-inputs.text
                        name="name"
                        :value="old('name', $user->name)"
                        :label="__('account.profile.details.name')"
                        autocomplete="name"
                        required
                    />

                    <x-inputs.text
                        name="email"
                        type="email"
                        :value="old('email', $user->email)"
                        :label="__('account.profile.details.email')"
                        autocomplete="email"
                        required
                    />

                    <x-inputs.select name="locale" :label="__('account.profile.details.language')" required>
                        @foreach (config('locales') as $code => $locale)
                            <option value="{{ $code }}" @selected(old('locale', $user->preferredLocale()) === $code)>
                                {{ $locale['label'] }}
                            </option>
                        @endforeach
                    </x-inputs.select>

                    <div class="account-form__pair grid-split grid">
                        <x-inputs.select name="profile_country_id" :label="__('account.profile.details.country')">
                            <option value="">{{ __('account.profile.details.country_placeholder') }}</option>

                            @foreach ($countries as $country)
                                <option
                                    value="{{ $country->id }}"
                                    @selected((string) old('profile_country_id', $user->country_id) === (string) $country->id)
                                >
                                    {{ $country->name }} (+{{ $country->calling_code }})
                                </option>
                            @endforeach
                        </x-inputs.select>

                        <x-inputs.text
                            name="phone_number"
                            type="tel"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            :value="old('phone_number', $phone?->phone_number)"
                            :label="__('account.profile.details.phone_number')"
                            autocomplete="tel-national"
                        />
                    </div>

                    <div class="form-actions account-form__actions">
                        <x-button variant="secondary" type="button" class="js-only" data-account-form-cancel>
                            {{ __('account.profile.cancel') }}
                        </x-button>

                        <x-button type="submit">{{ __('account.profile.save') }}</x-button>
                    </div>
                </form>

                {{-- Where the shopper's orders are delivered: the address the
                     checkout starts from. --}}
                <form
                    class="card account-form form"
                    method="POST"
                    action="{{ route('account.address.update') }}"
                    data-account-form
                >
                    @csrf
                    @method('PATCH')

                    <div class="account-form__header">
                        <h2 class="account-panel__title">{{ __('account.profile.address.title') }}</h2>

                        <x-button
                            variant="tertiary"
                            type="button"
                            class="account-form__toggle js-only"
                            data-account-form-toggle
                        >
                            {{ __('account.profile.edit') }}
                        </x-button>
                    </div>

                    <p class="account-form__note">{{ __('account.profile.address.hint') }}</p>

                    <x-address-form :address="$address" />

                    <div class="form-actions account-form__actions">
                        <x-button variant="secondary" type="button" class="js-only" data-account-form-cancel>
                            {{ __('account.profile.cancel') }}
                        </x-button>

                        <x-button type="submit">{{ __('account.profile.save') }}</x-button>
                    </div>
                </form>

                {{-- The password is its own form: it is the one thing here that is
                     answered with a secret rather than a detail, so it has
                     nothing to show while it is locked — its fields appear with
                     the Edit that opens them. --}}
                <form
                    class="card account-form account-form--secrets form"
                    method="POST"
                    action="{{ route('account.password.update') }}"
                    data-account-form
                >
                    @csrf
                    @method('PUT')

                    <div class="account-form__header">
                        <h2 class="account-panel__title">{{ __('account.profile.password.title') }}</h2>

                        <x-button
                            variant="tertiary"
                            type="button"
                            class="account-form__toggle js-only"
                            data-account-form-toggle
                        >
                            {{ __('account.profile.edit') }}
                        </x-button>
                    </div>

                    <x-inputs.text
                        name="current_password"
                        type="password"
                        :label="__('account.profile.password.current')"
                        autocomplete="current-password"
                        required
                    />

                    <x-inputs.password name="password" :label="__('account.profile.password.new')" />

                    <x-inputs.text
                        name="password_confirmation"
                        type="password"
                        :label="__('account.profile.password.confirmation')"
                        autocomplete="new-password"
                        required
                    />

                    <div class="form-actions account-form__actions">
                        <x-button variant="secondary" type="button" class="js-only" data-account-form-cancel>
                            {{ __('account.profile.cancel') }}
                        </x-button>

                        <x-button type="submit">{{ __('account.profile.password.submit') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts::app>
