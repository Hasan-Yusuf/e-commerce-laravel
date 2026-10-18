<?php

return [
    'title' => 'My wishlist',
    'count' => ':count item|:count items',

    'empty' => [
        'message' => 'Products I save with the heart icon appear here, ready to move into my cart.',
    ],

    'item' => [
        'unavailable_product' => 'This product is no longer available.',
        'inactive_product' => 'This product is no longer available for purchase.',
        'out_of_stock' => 'This product is out of stock.',
    ],

    'actions' => [
        'add' => 'Add to wishlist',
        'remove' => 'Remove from wishlist',
        'move_to_cart' => 'Move to cart',
        'move_all_to_cart' => 'Move everything to cart',
    ],

    'notify' => [
        'added_title' => ':name saved to my wishlist',
        'added_body' => 'I can find it in my wishlist at any time.',
        'exists_title' => 'Already in my wishlist',
        'exists_body' => ':name is already saved to my wishlist.',
        'removed_title' => 'Item removed from my wishlist',
        'moved_to_cart_title' => ':name moved to my cart',
        'moved_to_cart_body' => ':count :name is now in my cart.',
        'moved_to_cart_clamped_title' => 'Only :count available',
        'moved_to_cart_clamped_body' => 'Only :count :name available; my cart was adjusted.',
        'moved_to_cart_unavailable_title' => 'Product unavailable',
        'moved_to_cart_unavailable_body' => ':name is out of stock, so it stayed in my wishlist.',
        'moved_from_cart_title' => 'Moved to my wishlist',
        'moved_from_cart_body' => ':name was moved from my cart to my wishlist.',
        'already_wishlisted_body' => ':name was already saved; it was removed from my cart.',
        'moved_from_cart_failed_title' => 'Could not move to my wishlist',
        'moved_from_cart_failed_body' => 'This product is no longer available.',
        'moved_all_from_cart_title' => 'Cart moved to my wishlist',
        'moved_all_from_cart_body' => ':count item moved to my wishlist|:count items moved to my wishlist',
        'moved_all_from_cart_kept' => ':count item is no longer available and stayed in my cart.|:count items are no longer available and stayed in my cart.',
        'moved_all_to_cart_title' => 'Wishlist moved to my cart',
        'moved_all_to_cart_body' => ':count item moved to my cart|:count items moved to my cart',
        'moved_all_to_cart_clamped' => ':count item was limited to the available stock.|:count items were limited to the available stock.',
        'moved_all_to_cart_unavailable' => ':count item is no longer available and stayed in my wishlist.|:count items are no longer available and stayed in my wishlist.',
        'nothing_to_move_title' => 'Nothing to move',
        'nothing_moved_title' => 'Nothing could be moved',
    ],
];
