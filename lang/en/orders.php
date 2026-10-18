<?php

return [
    'title' => 'My orders',
    'subtitle' => 'Everything I have ordered, and where each order stands.',
    'placed_on' => 'Placed on :date',

    'index' => [
        'number' => 'Order',
        'date' => 'Date',
        'status' => 'Status',
        'payment' => 'Payment',
        'total' => 'Total',
        'action' => 'Action',
        'view' => 'View order',
        'per_page' => 'Orders per page',
        'per_page_apply' => 'Apply the number of orders',
    ],

    'empty' => [
        'title' => 'I have not placed an order yet',
        'message' => 'Browse our products and my orders will show up here.',
        'action' => 'Continue shopping',
    ],

    'item' => [
        'sku' => 'SKU :sku',
        'quantity' => '× :count',
    ],

    'summary' => [
        'title' => 'Order summary',
        'subtotal' => 'Subtotal',
        'shipping' => 'Shipping',
        'tax' => 'Tax',
        'discount' => 'Discount',
        'total' => 'Total',
    ],

    'show' => [
        'title' => 'Order :number',
        'breadcrumb' => 'Order details',
        'address' => 'Delivery address',
        'delivery' => 'Delivery method',
        'note' => 'Order note',
        'payment' => 'Payment',
        'back' => 'Back to my orders',
    ],

    'confirmation' => [
        'title' => 'Thank you for my order',
        'received' => 'My order is in — we will get it on its way.',
        'number' => 'Order :number',
        'estimated_delivery' => 'Estimated delivery: 2 to 4 business days.',
        'ready_for_pickup' => 'Ready for collection from our store.',
        'continue_shopping' => 'Continue shopping',
        'view_order' => 'View order',
    ],
];
