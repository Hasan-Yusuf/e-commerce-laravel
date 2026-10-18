<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\FlashMessage;
use App\Http\Requests\Account\UpdateAddressRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\User;
use App\Services\Account\AccountService;
use App\Services\Account\AddressService;
use App\Services\Order\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The shopper's own area: who they are, the way into the rest of it, and the
 * details and password they can change.
 *
 * Everything here is the signed-in shopper's own, so the whole area is behind
 * `auth` and every page reads the account it was opened by.
 */
final class AccountController extends Controller
{
    public function __construct(
        private OrderService $ordersService,
        private AccountService $accountsService,
        private AddressService $addresses,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('account.index', [
            'user' => $user,
            'orders' => $this->ordersService->getRecentFor($user),
        ]);
    }

    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('account.edit', [
            'user' => $user->loadMissing('phone'),
            'address' => $this->addresses->shippingAddressDataFor($user),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $request->profile();

        $this->accountsService->updateProfile($user, $profile);

        session()->put('locale', $profile->locale);

        return redirect()
            ->route('account.edit')
            ->with(FlashMessage::success()->withTitle(__('account.profile.saved'))->toSession());
    }

    public function updateAddress(UpdateAddressRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->addresses->updateShippingAddress($user, $request->address());

        return redirect()
            ->route('account.edit')
            ->with(
                FlashMessage::success()
                    ->withTitle(__('account.profile.address.saved'))
                    ->toSession(),
            );
    }

    public function updatePassword(
        UpdatePasswordRequest $request,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->accountsService->updatePassword(
            $user,
            (string) $request->string('password'),
        );

        return redirect()
            ->route('account.edit')
            ->with(
                FlashMessage::success()
                    ->withTitle(__('account.profile.password_saved'))
                    ->toSession(),
            );
    }
}
