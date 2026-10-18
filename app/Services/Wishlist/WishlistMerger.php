<?php

declare(strict_types=1);

namespace App\Services\Wishlist;

use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\DB;

/**
 * Folds a guest (session) wishlist into a signed-in account's, dropping the
 * guest copy of anything the account already saved.
 *
 * The guest session id is passed explicitly because signing in regenerates the
 * session: by the time the account is authenticated, session() no longer
 * resolves the rows the guest list was stored against.
 */
final class WishlistMerger
{
    public function merge(User $user, ?string $sessionId = null): void
    {
        $guestItems = WishlistItem::query()
            ->whereNull('user_id')
            ->where('session_id', $sessionId ?? session()->getId())
            ->get();

        if ($guestItems->isEmpty()) {
            return;
        }

        DB::transaction(static function () use ($guestItems, $user): void {
            $savedProductIds = WishlistItem::query()
                ->where('user_id', $user->getKey())
                ->pluck('product_id')
                ->map(static fn (mixed $productId): int => (int) $productId)
                ->all();

            foreach ($guestItems as $item) {
                if (in_array((int) $item->product_id, $savedProductIds, true)) {
                    $item->delete();

                    continue;
                }

                $item->update([
                    'user_id' => $user->getKey(),
                    'session_id' => null,
                ]);

                $savedProductIds[] = (int) $item->product_id;
            }
        });
    }
}
