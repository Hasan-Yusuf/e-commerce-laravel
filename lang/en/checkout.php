<?php

return [
    'title' => 'Checkout',
    'subtitle' => 'Answer a few quick steps and we will take it from there.',

    'steps' => [
        'label' => 'Checkout steps',

        'state' => [
            'current' => 'Current step',
            'complete' => 'Completed',
            'pending' => 'Not started yet',
        ],

        'customer' => [
            'title' => 'My Info',
        ],
        'shipping_address' => [
            'title' => 'Shipping address',
        ],
        'delivery' => [
            'title' => 'Delivery',
        ],
        'payment' => [
            'title' => 'Payment',
        ],
        'review' => [
            'title' => 'Review',
        ],
    ],

    'fields' => [
        'name' => 'Full name',
        'email' => 'Email address',
        'country' => 'Country',
        'phone' => 'Phone number',

        'street_address' => 'Street address',
        'city' => 'City',
        'state' => 'State',
        'country' => 'Country',
        'country_placeholder' => 'Select a country',
        'postal_code' => 'Postal code',
        'save_to_account' => 'Save this address to my account',
    ],

    'actions' => [
        'continue' => 'Continue',
        'back' => 'Back',
        'edit' => 'Edit :subject',
    ],

    'delivery' => [
        'legend' => 'Delivery method',

        'methods' => [
            'standard' => [
                'description' => 'Delivered to your address within 2 to 4 business days.',
            ],

            'pickup' => [
                'description' => 'Collect your order from our store — we will tell you when it is ready.',
            ],
        ],
    ],

    'payment' => [
        'legend' => 'Payment method',

        'methods' => [
            'cod' => [
                'description' => 'Pay for your order when it is delivered.',
            ],
        ],
    ],

    'review' => [
        'items' => 'Items',
        'quantity' => '× :count',
    ],

    'coupon' => [
        'placeholder' => 'Enter coupon code',
        'apply' => 'Apply',
        'remove' => 'Remove',
        'invalid' => 'This coupon code is not valid.',
        'expired' => 'This coupon code has expired.',
        'exhausted' => 'This coupon code has been used up.',
        'min_order' => 'Your order does not meet the minimum amount for this coupon.',
    ],

    'place' => [
        'action' => 'Place order',
        'note' => 'Order note (optional)',
        'guest_notice' => 'Sign in or create an account to place your order. Your cart and the answers on this page are kept.',
        'stock_changed_title' => 'Some items are no longer available',
        'stock_changed_message' => 'The quantities in your cart were adjusted to what is in stock. Please check your order and place it again.',
    ],
];
