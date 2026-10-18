<?php

return [
    'title' => 'Shopping cart',
    'count' => ':count item|:count items',

    'empty' => [
        'title' => 'My cart is empty',
        'message' => 'Browse our products and add something you like.',
        'action' => 'Continue shopping',
    ],

    'item' => [
        'image' => 'Image',
        'product' => 'Product',
        'unit_price' => 'Unit price',
        'quantity' => 'Quantity',
        'line_total' => 'Total',
        'action' => 'Action',
        'unavailable_product' => 'This product is no longer available.',
        'inactive_product' => 'This product is no longer available for purchase.',
        'out_of_stock' => 'This product is out of stock.',
        'exceeds_stock' => 'Only :count left in stock.',
        'price_changed' => 'The price changed from :old to :new.',
    ],

    'actions' => [
        'update' => 'Update',
        'remove' => 'Remove',
        'move_to_wishlist' => 'Move to wishlist',
        'move_all_to_wishlist' => 'Move everything to wishlist',
    ],

    'summary' => [
        'title' => 'Order summary',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
        'total' => 'Total',
        'coupon' => 'Coupon code',
        'coupon_placeholder' => 'Coming soon',
        'coupon_coming_soon' => 'Coupons are coming soon.',
        'checkout' => 'Proceed to checkout',
    ],

    'status' => [
        'added' => 'Added to cart.',
        'updated' => 'Cart updated.',
        'removed' => 'Item removed from cart.',
        'unavailable' => 'This product is out of stock.',
        'clamped' => 'Only :count available; my cart was adjusted.',
    ],

    'notify' => [
        'added_title' => ':name added to the cart successfully',
        'added_body' => ':count :name has been added to the cart.',
        'clamped_title' => 'Quantity adjusted',
        'clamped_body' => 'Only :count :name available; my cart was adjusted.',
        'unavailable_title' => 'Product unavailable',
        'unavailable_body' => ':name is out of stock.',
        'updated_title' => 'Cart updated',
        'updated_body' => 'Quantity set to :count.',
        'removed_title' => 'Item removed from the cart',
    ],
];
