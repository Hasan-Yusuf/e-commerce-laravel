<?php

return [
    'sections' => [
        'main' => 'Main Information',
        'settings' => 'Category Settings',
    ],

    'fields' => [
        'name_en' => 'Name ( English )',
        'name_ar' => 'Name ( Arabic )',

        'slug' => 'Slug',

        'description_en' => 'Description ( English )',
        'description_ar' => 'Description ( Arabic )',

        'parent' => 'Main category',

        'activated_at' => 'Active',

        'sort_order' => 'Priority Order',

        'image' => 'Image',
    ],

    'labels' => [
        'navigation' => 'Categories',

        'model_name' => 'Category',

        'plural_model_name' => 'Categories',
    ],

    'helpers' => [
        'parent' => 'If this is a subcategory, Select the main category',

        'slug' => 'Filled in from the English name. It is the address of the category in the shop.',

        'image' => 'Any supported image. A WebP copy is made for the shop, and see-through areas are kept.',

        'activated_at' => 'Disable to hide the category from customers',

        'sort_order' => 'The order of this category',
    ],

    'columns' => [
        'thumbnail' => 'Thumbnail',

        'name' => 'Name',

        'parent' => 'Main Category',

        'types' => 'Types',
    ],

    'filters' => [
        'parent_name' => 'Main Category',
    ],

    'groups' => [
        'parent' => 'Main Category',

        'errors' => [
            'no_parent' => 'No Main Category',
        ],
    ],

    'empty_state' => [
        'heading' => 'No categories yet',

        'description' => 'Add a category to organise your catalogue by clicking the following button.',
    ],
];
