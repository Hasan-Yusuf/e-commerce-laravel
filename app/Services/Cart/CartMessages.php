<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\DTOs\CartItemResult;
use App\DTOs\FlashMessage;
use App\Models\Product;

final class CartMessages
{
    public static function forAdded(
        CartItemResult $result,
        Product $product,
    ): FlashMessage {
        $outcome = self::stockOutcome($result, $product);

        return $outcome ?? self::added($product, self::quantity($result));
    }

    public static function forUpdated(
        CartItemResult $result,
        ?Product $product,
    ): FlashMessage {
        $outcome = self::stockOutcome($result, $product);

        return $outcome ?? self::updated(self::quantity($result));
    }

    public static function added(Product $product, int $quantity): FlashMessage
    {
        $title = __('carts.notify.added_title', ['name' => $product->name]);

        $body = __('carts.notify.added_body', [
            'count' => $quantity,
            'name' => $product->name,
        ]);

        return FlashMessage::success()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function clamped(?Product $product, int $quantity): FlashMessage
    {
        $title = __('carts.notify.clamped_title');

        $body = $product === null
            ? __('carts.status.clamped', ['count' => $quantity])
            : __('carts.notify.clamped_body', [
                'count' => $quantity,
                'name' => $product->name,
            ]);

        return FlashMessage::warning()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function unavailable(?Product $product): FlashMessage
    {
        $title = __('carts.notify.unavailable_title');

        $body = $product === null
            ? __('carts.item.unavailable_product')
            : __('carts.notify.unavailable_body', [
                'name' => $product->name,
            ]);

        return FlashMessage::warning()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function updated(int $quantity): FlashMessage
    {
        $title = __('carts.notify.updated_title');

        $body = __('carts.notify.updated_body', ['count' => $quantity]);

        return FlashMessage::success()
            ->withTitle($title)
            ->withBody($body);
    }

    public static function removed(): FlashMessage
    {
        $title = __('carts.notify.removed_title');

        return FlashMessage::success()
            ->withTitle($title);
    }

    /**
     * The stock-driven message a mutation reports regardless of whether the
     * line was added or updated, or null when neither applies.
     */
    private static function stockOutcome(
        CartItemResult $result,
        ?Product $product,
    ): ?FlashMessage {
        if ($result->unavailable) {
            return self::unavailable($product);
        }

        if ($result->clamped) {
            return self::clamped($product, self::quantity($result));
        }

        return null;
    }

    private static function quantity(CartItemResult $result): int
    {
        return (int) ($result->item->quantity ?? 0);
    }
}
