<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Folds a guest (session) cart into a signed-in account's cart on login,
 * combining duplicate products and clamping to availability.
 */
final class CartMerger
{
    public function __construct(
        private CartResolver $resolver,
    ) {}

    /**
     * The guest session id is passed explicitly because signing in regenerates
     * the session: by the time the account is authenticated, session() no longer
     * resolves the row the guest cart was stored against.
     */
    public function merge(User $user, ?string $sessionId = null): void
    {
        $guestCart = $this->resolver->forSession(
            $sessionId ?? $this->resolver->sessionId(),
        );

        if ($guestCart === null) {
            return;
        }

        DB::transaction(function () use ($guestCart, $user): void {
            $userCart = $this->resolver->forUser((int) $user->getKey()) ?? Cart::create([
                'user_id' => $user->getKey(),
                'session_id' => null,
                'expires_at' => now()->addDays(CartResolver::USER_LIFETIME_DAYS),
            ]);

            foreach ($guestCart->cartItems()->with('product')->get() as $sourceItem) {
                /** @var CartItem $sourceItem */
                $product = $sourceItem->product;
                $available = $product === null ? 0 : $product->getAvailableStock();

                if (
                    $product === null
                    || ! $product->isActive()
                    || $available <= 0
                ) {
                    continue;
                }

                /** @var CartItem $target */
                $target = $userCart
                    ->cartItems()
                    ->firstOrNew(['product_id' => $sourceItem->product_id]);

                /** @var int|null $targetQuantity */
                $targetQuantity = $target->quantity;

                $combined = ($targetQuantity ?? 0) + (int) $sourceItem->quantity;

                $target->fill([
                    'quantity' => min($combined, $available),
                    'price_in_fils' => $sourceItem->price_in_fils,
                ])->save();
            }

            $guestCart->delete();
            $this->resolver->touch($userCart);
        });
    }
}
