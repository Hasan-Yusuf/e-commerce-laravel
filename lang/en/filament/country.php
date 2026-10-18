<?php

return [
    'labels' => [
        'model_name' => 'Country',

        'plural_model_name' => 'Countries',
    ],

    'sections' => [
        'number_information' => 'Number Information',
    ],

    'fields' => [
        'name_en' => 'Name  ( English )',
        'name_ar' => 'Name  ( Arabic )',

        'slug' => 'Slug',

        'calling_code' => 'Calling Code',

        'phone_length' => 'Phone Length',
    ],

    'placeholders' => [
        'name_en' => 'Bahrain',
        'name_ar' => 'البحرين',

        'calling_code' => '+973',

        'phone_length' => '8',
    ],

    'helpers' => [
        'slug' => 'Filled in from the English name.',
    ],

    'columns' => [
        'name' => 'Name',

        'calling_code' => 'Calling Code',

        'phone_length' => 'Phone Length',

        'orders_count' => 'Orders Count',
    ],

    'empty_state' => [
        'heading' => 'No countries yet',

        'description' => 'Add the countries your customers ship to by clicking the following button.',
    ],
];
