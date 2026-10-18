<?php

declare(strict_types=1);

namespace App\Services\Wishlist;

use App\DTOs\CartBulkMoveResult;
use App\DTOs\FlashMessage;
use App\DTOs\WishlistBulkMoveResult;
use App\Enums\ToastType;

/**
 * The toast for a move of the whole list: what left it, what had to stay behind,
 * and whether that makes the outcome a warning rather than a success.
 */
final class WishlistBulkMessages
{
    /**
     * The whole cart moved onto the wishlist: how many lines left it, and how
     * many had to stay behind because their product is gone.
     */
    public static function movedAllFromCart(CartBulkMoveResult $result): FlashMessage
    {
        if ($result->isEmpty()) {
            return self::nothingToMove();
        }

        return self::message(
            $result->moved > 0
                ? __('wishlist.notify.moved_all_from_cart_title')
                : __('wishlist.notify.nothing_moved_title'),
            self::countedBody([
                [$result->moved, 'wishlist.notify.moved_all_from_cart_body'],
                [$result->kept, 'wishlist.notify.moved_all_from_cart_kept'],
            ]),
            $result->kept > 0 ? ToastType::WARNING : ToastType::SUCCESS,
        );
    }

    /**
     * The whole wishlist moved into the cart: how many entries left it, how
     * many arrived with a quantity trimmed to stock, and how many stayed saved
     * because their product cannot be bought right now.
     */
    public static function movedAllToCart(WishlistBulkMoveResult $result): FlashMessage
    {
        if ($result->isEmpty()) {
            return self::nothingToMove();
        }

        return self::message(
            $result->moved > 0
                ? __('wishlist.notify.moved_all_to_cart_title')
                : __('wishlist.notify.nothing_moved_title'),
            self::countedBody([
                [$result->moved, 'wishlist.notify.moved_all_to_cart_body'],
                [$result->clamped, 'wishlist.notify.moved_all_to_cart_clamped'],
                [
                    $result->unavailable,
                    'wishlist.notify.moved_all_to_cart_unavailable',
                ],
            ]),
            $result->unavailable > 0 || $result->clamped > 0
                ? ToastType::WARNING
                : ToastType::SUCCESS,
        );
    }

    /**
     * What could not come along whole is what makes a move a warning rather
     * than a success; the message itself is the same either way.
     */
    private static function message(
        string $title,
        ?string $body,
        ToastType $type,
    ): FlashMessage {
        return FlashMessage::success()
            ->withType($type)
            ->withTitle($title)
            ->withBody($body);
    }

    /**
     * The list was empty, so the move had nothing to do.
     */
    private static function nothingToMove(): FlashMessage
    {
        return FlashMessage::info()
            ->withTitle(__('wishlist.notify.nothing_to_move_title'));
    }

    /**
     * The counted lines of a toast body, dropping the ones that do not apply.
     *
     * @param  array<int, array{0: int, 1: string}>  $parts
     */
    private static function countedBody(array $parts): ?string
    {
        $lines = [];

        foreach ($parts as [$count, $key]) {
            if ($count <= 0) {
                continue;
            }

            $lines[] = trans_choice($key, $count);
        }

        return self::body($lines);
    }

    /**
     * Join the parts of a toast body, dropping the ones that do not apply, so
     * the caller never has to trim a half-sentence off the end.
     *
     * @param  array<int, ?string>  $parts
     */
    private static function body(array $parts): ?string
    {
        $lines = array_filter(
            $parts,
            static fn (?string $part): bool => $part !== null && $part !== '',
        );

        return $lines === [] ? null : implode(' ', $lines);
    }
}
