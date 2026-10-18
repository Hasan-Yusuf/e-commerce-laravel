@php
    $customer = $summary->customer;

    // The countries a phone number can be given for — the same list the address
    // step beside it offers.
    $countries = \App\Models\Country::query()->orderBy('name_en')->get();
@endphp

<form class="checkout-form form" method="POST" action="{{ route('checkout.customer.store') }}" data-submit-once>
    @csrf

    <x-inputs.text
        name="name"
        :value="old('name', $customer->name)"
        :label="__('checkout.fields.name')"
        autocomplete="name"
        required
    />

    <x-inputs.text
        name="email"
        type="email"
        :value="old('email', $customer->email)"
        :label="__('checkout.fields.email')"
        autocomplete="email"
        required
    />

    {{-- The number and the country it is given for read as one control: the code
         is the number's own prefix, so only the calling code is shown — the
         country's name would read as the address step's field rather than this
         one. Giving either half of the number means giving both, because a
         number is only as long as its country's numbering plan says and a
         country with no number is not a phone at all (see
         StoreCustomerRequest). --}}
    <div class="checkout-form__phone">
        <x-inputs.select
            name="country_id"
            :label="__('checkout.fields.country')"
            autocomplete="tel-country-code"
        >
            <option value="">+</option>

            @foreach ($countries as $country)
                <option
                    value="{{ $country->id }}"
                    @selected((string) old('country_id', $customer->countryId) === (string) $country->id)
                >
                    +{{ $country->calling_code }}
                </option>
            @endforeach
        </x-inputs.select>

        <x-inputs.text
            name="phone_number"
            type="tel"
            inputmode="numeric"
            pattern="[0-9]*"
            :value="old('phone_number', $customer->phone)"
            :label="__('checkout.fields.phone')"
            autocomplete="tel-national"
        />
    </div>

    <div class="form-actions">
        <x-button type="submit" block> {{ __('checkout.actions.continue') }} </x-button>
    </div>
</form>
