<?php

return [
    'labels' => [
        'model_name' => 'Coupon',
        'plural_model_name' => 'Coupons',
    ],

    'sections' => [
        'general_information' => 'General Information',

        'validity' => 'Validity',

        'usage' => 'Usage',

        'used_orders' => 'Orders Using This Coupon',
    ],

    'fields' => [
        'code' => 'Code',

        'description' => 'Description',

        'type' => 'Type',

        'value' => 'Value',

        'maximum_discount' => 'Maximum discount',

        'min_order_amount' => 'Minimum order amount',

        'usage_limit' => 'Usage limit',

        'activated_at' => 'Activate at',

        'starts_at' => 'Starts',

        'expires_at' => 'Expires',
    ],

    'helpers' => [
        'value' => 'A percentage takes this share off the subtotal; a fixed amount takes this much off it.',

        'maximum_discount' => 'The most this coupon may take off, whatever the value works out to.',

        'usage_limit' => 'Leave empty for unlimited uses.',
    ],

    'columns' => [
        'code' => 'Code',

        'type' => 'Type',

        'value' => 'Value',

        'used_count' => 'Used',

        'usage_limit' => 'Limit',

        'unlimited' => 'Unlimited',

        'activated_at' => 'Activate at',

        'starts_at' => 'Starts',

        'expires_at' => 'Expires',

        'is_currently_active' => 'Active Now',
    ],

    'filters' => [
        'type' => 'Type',

        'is_active' => 'Is Active',

        'active' => 'Active',

        'inactive' => 'Inactive',

        'active_now' => 'Active Now',
    ],

    'infolists' => [
        'code' => 'Code',

        'description' => 'Description',

        'type' => 'Type',

        'value' => 'Value',

        'maximum_discount' => 'Maximum discount',

        'min_order_amount' => 'Minimum order amount',

        'usage_limit' => 'Usage limit',

        'unlimited' => 'Unlimited',

        'used_count' => 'Used count',

        'activated_at' => 'Activated at',

        'starts_at' => 'Starts',

        'expires_at' => 'Expires',

        'orders_count' => 'Orders',

        'total_discount' => 'Total discount given',
    ],

    'copy_messages' => [
        'code' => 'Code copied',
    ],

    'empty_state' => [
        'heading' => 'No coupons yet',

        'description' => 'Add a coupon to run a promotion by clicking the following button.',
    ],
];
