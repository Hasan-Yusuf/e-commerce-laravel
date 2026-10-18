<?php

return [
    'labels' => [
        'navigation' => 'Types',

        'model_name' => 'Type',

        'plural_model_name' => 'Types',

        'update_products' => 'Update products',
    ],

    'fields' => [
        'name' => 'Name',

        'category' => 'Category',

        'specifications' => 'Specifications',
        'spec_name_en' => 'Name ( English )',
        'spec_name_ar' => 'Name ( Arabic )',
        'spec_value_type' => 'Value Type',
        'spec_unit' => 'Unit',
        'spec_options' => 'Options',
        'spec_option_en' => 'Option ( English )',
        'spec_option_ar' => 'Option ( Arabic )',
        'spec_values' => 'Values',
        'spec_value' => 'Value',
        'spec_is_filterable' => 'Filterable',
        'spec_filter_type' => 'Filter Type',
    ],

    'placeholders' => [
        'name' => 'Smartphones',

        'spec_name_en' => 'Storage',
        'spec_name_ar' => 'التخزين',

        'spec_unit' => 'GB',
    ],

    'columns' => [
        'name' => 'Name',

        'category' => 'Category',

        'specs' => 'Specification',

        'specs_need_review' => 'Products out of date',
    ],

    'filters' => [
        'specs' => 'Specification',
    ],

    'groups' => [
        'category' => 'Category',

        'errors' => [
            'no_category' => 'No Category',
        ],
    ],

    'actions' => [
        'add_spec' => 'Add Specification',

        'add_option' => 'Add Option',

        'add_value' => 'Add Value',

        'update_products' => 'Update products',

        'update_products_tooltip' => 'Opens the products of this type so their values can be rebuilt from the specifications that changed.',

        'save_products' => 'Save products',

        'save_products_tooltip' => 'Rebuilds the products from the values you entered and finishes.',
    ],

    'helpers' => [
        'spec_values' => 'Fill these in when the number takes a fixed set of values, like the storage sizes of a phone (64, 128, 256): the value is then picked rather than typed, and the storefront filters it as a list instead of a range.',

        'update_products' => 'The specifications of this type changed, so its products no longer answer for them. Go over each product below — saving rebuilds its values from the specifications, in both languages and without what a removed specification used to hold.',

        'specs_need_review' => 'The specifications changed after these products were filled in.',
    ],

    'notifications' => [
        'products_need_review' => [
            'title' => 'The specifications changed',

            'body' => 'Go through the values of this type\'s products before they are trusted again.',
        ],

        'products_updated' => [
            'title' => 'Products updated',

            'body' => 'Every product under this type now answers for its specifications.',
        ],
    ],

    'validation' => [
        'range_requires_number' => 'A range filter can only be used with a numeric value type.',
    ],

    'empty_state' => [
        'heading' => 'No types yet',

        'description' => 'Add a type to define a product\'s specifications by clicking the following button.',
    ],
];
