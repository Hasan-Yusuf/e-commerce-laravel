<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Wishlist\WishlistService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Owns the bridge between a guest session and a signed-in account: verifying
 * credentials, creating customer accounts, and folding the guest cart and
 * wishlist into the account when a shopper signs in.
 *
 * The guest session id is always captured before the account is authenticated,
 * because signing in regenerates the session — by the time Auth::user() is set,
 * session() no longer resolves the rows the guest cart and wishlist were stored
 * against (see CartService::merge and WishlistService::merge).
 */
class AuthService
{
    public function __construct(
        private CartService $cartService,
        private WishlistService $wishlistService,
    ) {}

    /**
     * Verify credentials, sign the shopper in and carry their guest cart and
     * wishlist onto the account. Returns null when the credentials do not
     * match, so the caller can raise one generic error.
     *
     * @param  array{email: string, password: string}  $credentials
     */
    public function attempt(array $credentials): ?User
    {
        $guestSessionId = session()->getId();

        if (! Auth::attempt($credentials)) {
            return null;
        }

        return $this->completeLogin($guestSessionId);
    }

    /**
     * The same as attempt(), but the sign-in survives a browser restart (the
     * "remember me" box).
     *
     * @param  array{email: string, password: string}  $credentials
     */
    public function attemptRemembering(array $credentials): ?User
    {
        $guestSessionId = session()->getId();

        if (! Auth::attempt($credentials, true)) {
            return null;
        }

        return $this->completeLogin($guestSessionId);
    }

    /**
     * Create an active customer account in the current locale, fold the guest
     * cart and wishlist into it and sign the new shopper in.
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'role' => UserRole::CUSTOMER,
                'status' => UserStatus::ACTIVE,
                'locale' => app()->getLocale(),
            ]);

            $this->mergeGuestState($user, session()->getId());

            Auth::login($user);

            return $user;
        });
    }

    /**
     * Fold the guest cart and wishlist into the account.
     */
    public function mergeGuestState(User $user, string $guestSessionId): void
    {
        $this->cartService->merge($user, $guestSessionId);
        $this->wishlistService->merge($user, $guestSessionId);
    }

    /**
     * Carry the guest cart and wishlist captured before authentication onto
     * the now signed-in account.
     */
    private function completeLogin(string $guestSessionId): User
    {
        /** @var User $user */
        $user = Auth::user();

        $this->mergeGuestState($user, $guestSessionId);

        return $user;
    }
}
