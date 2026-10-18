<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\FlashMessage;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartMessages;
use App\Services\Cart\CartService;
use App\Services\Cart\CartWishlistTransferer;
use App\Services\Wishlist\WishlistMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CartController extends Controller
{
    public function index(): View
    {
        return view('cart.index');
    }

    public function add(
        StoreCartItemRequest $request,
        CartService $cart,
    ): RedirectResponse {
        $product = Product::query()->findOrFail(
            $request->integer('product_id'),
        );

        $result = $cart->add($product, $request->integer('quantity'));

        return $this->flash(CartMessages::forAdded($result, $product));
    }

    public function update(
        UpdateCartItemRequest $request,
        CartItem $item,
        CartService $cart,
    ): RedirectResponse {
        $result = $cart->update($item, $request->integer('quantity'));

        return $this->flash(CartMessages::forUpdated($result, $item->product));
    }

    public function remove(CartItem $item, CartService $cart): RedirectResponse
    {
        $cart->remove($item);

        return $this->flash(CartMessages::removed());
    }

    public function moveToWishlist(
        CartItem $item,
        CartWishlistTransferer $transferer,
    ): RedirectResponse {
        $product = $item->product;

        $result = $transferer->moveToWishlist($item);

        if ($result->unavailable) {
            return $this->flash(WishlistMessages::moveFromCartFailed());
        }

        return $this->flash(
            $result->alreadyExists
                ? WishlistMessages::alreadyWishlisted($product)
                : WishlistMessages::movedFromCart($product),
        );
    }

    /**
     * Move the whole cart onto the wishlist, for the shoppers starting over on
     * the other list. Lines whose product is gone stay where they are.
     */
    public function moveAllToWishlist(CartWishlistTransferer $transferer): RedirectResponse
    {
        return $this->flash(
            WishlistMessages::movedAllFromCart($transferer->moveAllToWishlist()),
        );
    }

    private function flash(FlashMessage $message): RedirectResponse
    {
        return $this->back()->with($message->toSession());
    }

    private function back(): RedirectResponse
    {
        return redirect()->back(fallback: route('cart.index'));
    }
}
