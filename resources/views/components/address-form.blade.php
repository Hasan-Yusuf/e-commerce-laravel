@props([
    // The address the form answers, as the checkout holds it.
    'address',
    // A signed-in shopper can keep the address on their account.
    'savable' => false,
])

@php
    $countries = \App\Models\Country::query()->orderBy('name_en')->get();
    $selectedCountry = old('country_id', $address->countryId);
@endphp

<div class="address-form">
    <x-inputs.text
        name="street_address"
        wrapper-class="address-form__wide"
        :value="old('street_address', $address->streetAddress)"
        :label="__('checkout.fields.street_address')"
        autocomplete="street-address"
        required
    />

    <x-inputs.text
        name="city"
        :value="old('city', $address->city)"
        :label="__('checkout.fields.city')"
        autocomplete="address-level2"
        required
    />

    <x-inputs.text
        name="state"
        :value="old('state', $address->state)"
        :label="__('checkout.fields.state')"
        autocomplete="address-level1"
        required
    />

    <x-inputs.select name="country_id" :label="__('checkout.fields.country')" autocomplete="country" required>
        <option value="">{{ __('checkout.fields.country_placeholder') }}</option>

        @foreach ($countries as $country)
            <option value="{{ $country->id }}" @selected((string) $selectedCountry === (string) $country->id)>
                {{ $country->name }}
            </option>
        @endforeach
    </x-inputs.select>

    <x-inputs.text
        name="postal_code"
        :value="old('postal_code', $address->postalCode)"
        :label="__('checkout.fields.postal_code')"
        autocomplete="postal-code"
        required
    />

    @if ($savable)
        {{-- A choice, not a field: it owns its own row and keeps the plain
             checkbox look rather than the box the form fields carry. --}}
        <div class="form-field address-form__wide address-form__save">
            <x-inputs.checkbox
                name="save_to_account"
                value="1"
                :checked="(bool) old('save_to_account')"
                :label="__('checkout.fields.save_to_account')"
            />
        </div>
    @endif
</div>
