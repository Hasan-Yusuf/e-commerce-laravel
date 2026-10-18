<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\FlashMessage;
use App\Http\Requests\Wishlist\StoreWishlistItemRequest;
use App\Models\Product;
use App\Models\WishlistItem;
use App\Services\Cart\CartWishlistTransferer;
use App\Services\Wishlist\WishlistMessages;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\RedirectResponse;

/**
 * The wishlist has no page of its own: it is rendered under the cart
 * (`cart.index#wishlist`), so only its mutations need HTTP entry points.
 */
final class WishlistController extends Controller
{
    public function add(
        StoreWishlistItemRequest $request,
        WishlistService $wishlist,
    ): RedirectResponse {
        $product = Product::query()->findOrFail(
            $request->integer('product_id'),
        );

        $result = $wishlist->add($product);

        return $this->flash(
            $result->alreadyExists
                ? WishlistMessages::exists($product)
                : WishlistMessages::added($product),
        );
    }

    public function remove(
        WishlistItem $item,
        WishlistService $wishlist,
    ): RedirectResponse {
        $wishlist->remove($item);

        return $this->flash(WishlistMessages::removed());
    }

    public function moveToCart(
        WishlistItem $item,
        CartWishlistTransferer $transferer,
    ): RedirectResponse {
        $product = $item->product;
        $result = $transferer->moveToCart($item);

        if ($result->unavailable || $product === null) {
            return $this->flash(
                WishlistMessages::moveToCartUnavailable($product),
            );
        }

        $quantity = (int) ($result->item->quantity ?? 1);

        return $this->flash(
            $result->clamped
                ? WishlistMessages::movedToCartClamped($product, $quantity)
                : WishlistMessages::movedToCart($product, $quantity),
        );
    }

    /**
     * Move the whole wishlist into the cart. Entries whose product cannot be
     * bought right now stay saved.
     */
    public function moveAllToCart(CartWishlistTransferer $transferer): RedirectResponse
    {
        return $this->flash(
            WishlistMessages::movedAllToCart($transferer->moveAllToCart()),
        );
    }

    private function flash(FlashMessage $message): RedirectResponse
    {
        return redirect()->back(fallback: route('cart.index'))->with($message->toSession());
    }
}
