<?php

return [
    'labels' => [
        'model_name' => 'Discount',
        'plural_model_name' => 'Discounts',
    ],

    'sections' => [
        'general_information' => 'General Information',

        'validity' => 'Validity',

        'apply_to' => 'Apply to',

        'discounted_products' => 'Discounted Products',

        'discounted_product_types' => 'Discounted Product Types',

        'discounted_categories' => 'Discounted Categories',

        'discounted_brands' => 'Discounted Brands',
    ],

    'fields' => [
        'name' => 'Name',

        'type' => 'Type',

        'value' => 'Value',

        'starts_at' => 'Starts',

        'ends_at' => 'Ends',

        'active' => 'Active',

        'activated_at' => 'Activate at',

        'products' => 'Products',

        'categories' => 'Categories',

        'product_types' => 'Product Types',

        'brands' => 'Brands',
    ],

    'helpers' => [
        'value' => 'A percentage takes this share off the price; a fixed amount takes this much off it.',
    ],

    'columns' => [
        'name' => 'Name',

        'type' => 'Type',

        'value' => 'Value',

        'products_count' => 'Products Count',

        'categories_count' => 'Categories Count',

        'types_count' => 'Types Count',

        'brands_count' => 'Brands Count',

        'starts_at' => 'Starts',

        'ends_at' => 'Ends',

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
        'name' => 'Name',

        'type' => 'Type',

        'value' => 'Value',

        'starts_at' => 'Starts',

        'ends_at' => 'Ends',

        'activated_at' => 'Activated at',
    ],

    'empty_state' => [
        'heading' => 'No discounts yet',

        'description' => 'Add a discount to run a promotion by clicking the following button.',
    ],
];
