<?php

declare(strict_types=1);

namespace App\Services\Wishlist;

use App\DTOs\CartBulkMoveResult;
use App\DTOs\FlashMessage;
use App\DTOs\WishlistBulkMoveResult;
use App\Models\Product;

/**
 * Builds the message a wishlist change is reported with, so the redirect
 * controller flow and the Livewire cart share one copy source.
 */
final class WishlistMessages
{
    public static function added(Product $product): FlashMessage
    {
        $title = __('wishlist.notify.added_title', [
            'name' => $product->name,
        ]);

        $body = __('wishlist.notify.added_body');

        return FlashMessage::success()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function exists(?Product $product): FlashMessage
    {
        $title = __('wishlist.notify.exists_title');

        $body = $product === null
            ? __('wishlist.item.unavailable_product')
            : __('wishlist.notify.exists_body', [
                'name' => $product->name,
            ]);

        return FlashMessage::info()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function removed(): FlashMessage
    {
        $title = __('wishlist.notify.removed_title');

        return FlashMessage::success()
            ->withTitle($title);
    }

    public static function movedToCart(
        Product $product,
        int $quantity,
    ): FlashMessage {
        $title = __('wishlist.notify.moved_to_cart_title', [
            'name' => $product->name,
        ]);

        $body = __('wishlist.notify.moved_to_cart_body', [
            'count' => $quantity,
            'name' => $product->name,
        ]);

        return FlashMessage::success()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function movedToCartClamped(
        Product $product,
        int $quantity,
    ): FlashMessage {
        $title = __('wishlist.notify.moved_to_cart_clamped_title', [
            'count' => $quantity,
        ]);

        $body = __('wishlist.notify.moved_to_cart_clamped_body', [
            'count' => $quantity,
            'name' => $product->name,
        ]);

        return FlashMessage::warning()
            ->withTitle($title)
            ->withBody($body);
    }

    /**
     * The product could not reach the cart, so it stays on the wishlist.
     */
    public static function moveToCartUnavailable(?Product $product): FlashMessage
    {
        $title = __('wishlist.notify.moved_to_cart_unavailable_title');

        $body = $product === null
            ? __('wishlist.item.unavailable_product')
            : __('wishlist.notify.moved_to_cart_unavailable_body', [
                'name' => $product->name,
            ]);

        return FlashMessage::warning()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function movedFromCart(Product $product): FlashMessage
    {
        $title = __('wishlist.notify.moved_from_cart_title');

        $body = __('wishlist.notify.moved_from_cart_body', [
            'name' => $product->name,
        ]);

        return FlashMessage::success()
            ->withTitle($title)
            ->withBody($body);
    }

    /**
     * The product was already saved, so only the cart line was dropped.
     */
    public static function alreadyWishlisted(Product $product): FlashMessage
    {
        $title = __('wishlist.notify.exists_title');

        $body = __('wishlist.notify.already_wishlisted_body', [
            'name' => $product->name,
        ]);

        return FlashMessage::info()
            ->withTitle($title)
            ->withBody($body);
    }

    /**
     * The cart line could not be moved, so it stays in the cart.
     */
    public static function moveFromCartFailed(): FlashMessage
    {
        $title = __('wishlist.notify.moved_from_cart_failed_title');

        $body = __('wishlist.notify.moved_from_cart_failed_body');

        return FlashMessage::warning()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function movedAllFromCart(CartBulkMoveResult $result): FlashMessage
    {
        return WishlistBulkMessages::movedAllFromCart($result);
    }

    public static function movedAllToCart(WishlistBulkMoveResult $result): FlashMessage
    {
        return WishlistBulkMessages::movedAllToCart($result);
    }
}
