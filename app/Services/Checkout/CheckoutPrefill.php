<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\DTOs\CheckoutState;
use App\DTOs\CustomerData;
use App\Enums\CheckoutStep;
use App\Models\User;
use App\Services\Account\AddressService;
use Illuminate\Support\Facades\Auth;

/**
 * Fills a checkout in from what the account already knows: the shopper's name,
 * email and phone, and the shipping address on file.
 *
 * A step the shopper has answered themselves is never touched — the prefill
 * only ever completes a state — so a guest who signs in part-way through the
 * checkout (the review sends them to log in and back) finds their details
 * waiting where they had not filled them in.
 */
class CheckoutPrefill
{
    public function __construct(
        private AddressService $addresses,
    ) {}

    public function apply(CheckoutState $state): CheckoutState
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $state;
        }

        if (! $state->plan->hasAnswered(CheckoutStep::CUSTOMER, $state->progress)) {
            $state = $state->withCustomer($this->customer($user));
        }

        if (! $state->plan->hasAnswered(CheckoutStep::SHIPPING_ADDRESS, $state->progress)) {
            $state = $state->withShippingAddress(
                // The address the account area reads back too, so the profile
                // tab and the checkout agree on which address the account holds.
                $this->addresses->shippingAddressDataFor($user),
            );
        }

        return $state;
    }

    private function customer(User $user): CustomerData
    {
        $phone = $user->phone;

        return CustomerData::fromArray([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $phone?->phone_number,
            'country_id' => $phone?->country_id,
        ]);
    }
}
