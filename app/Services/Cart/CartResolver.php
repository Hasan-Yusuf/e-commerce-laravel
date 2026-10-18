<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves which cart a shopper owns — the guest session's or the account's —
 * and owns that cart's lifetime so every writer extends it the same way.
 */
final class CartResolver
{
    /**
     * Guest (session) carts live for two weeks; signed-in customers keep theirs
     * for a month and a half.
     */
    public const int GUEST_LIFETIME_DAYS = 14;

    public const int USER_LIFETIME_DAYS = 45;

    /**
     * The current cart, creating one when none exists. Mutations that need a
     * cart to write to use this; read-only callers use find() so simply browsing
     * never creates rows. Expired carts are ignored here (not deleted); the
     * scheduled carts:prune command removes them separately.
     */
    public function current(): Cart
    {
        $cart = $this->find();

        if ($cart !== null) {
            return $cart;
        }

        $userId = Auth::id();

        return Cart::create([
            'user_id' => $userId,
            'session_id' => $userId === null ? $this->sessionId() : null,
            'expires_at' => now()->addDays($this->lifetimeDays($userId)),
        ]);
    }

    /**
     * The current, unexpired cart, or null. Never creates a cart.
     */
    public function find(): ?Cart
    {
        $userId = Auth::id();

        return $userId === null
            ? $this->forSession($this->sessionId())
            : $this->forUser((int) $userId);
    }

    public function forUser(mixed $userId): ?Cart
    {
        return Cart::query()
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function forSession(string $sessionId): ?Cart
    {
        return Cart::query()
            ->whereNull('user_id')
            ->where('session_id', $sessionId)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function assertOwned(CartItem $item): Cart
    {
        $cart = $this->find();

        if ($cart === null || (int) $item->cart_id !== (int) $cart->id) {
            abort(404);
        }

        return $cart;
    }

    /**
     * Extend the cart's expiry from the moment it was last used, using the
     * lifetime that matches its owner (guest session vs. user account).
     */
    public function touch(Cart $cart): void
    {
        $cart->update([
            'expires_at' => now()->addDays($this->lifetimeDays($cart->user_id)),
        ]);
    }

    /**
     * Delete carts whose expiry has passed. Run by the scheduled carts:prune
     * command, never from a request.
     */
    public function pruneExpired(): void
    {
        Cart::query()->where('expires_at', '<=', now())->delete();
    }

    public function sessionId(): string
    {
        return session()->getId();
    }

    /**
     * The lifetime, in days, for a cart owner: guest session carts are short
     * lived, registered customers keep their cart longer.
     */
    private function lifetimeDays(mixed $userId): int
    {
        return $userId === null
            ? self::GUEST_LIFETIME_DAYS
            : self::USER_LIFETIME_DAYS;
    }
}
