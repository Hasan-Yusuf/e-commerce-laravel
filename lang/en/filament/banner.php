<?php

return [
    'labels' => [
        'model_name' => 'Banner',
        'plural_model_name' => 'Banners',
    ],

    'fields' => [
        'image' => 'Image',

        'title_en' => 'Title ( English )',
        'title_ar' => 'Title ( Arabic )',

        'link' => 'Link',
    ],

    'placeholders' => [
        'title_en' => 'Enter title',
        'title_ar' => 'أدخل العنوان',

        'link' => 'www.example.com/example',
    ],

    'helpers' => [
        'image' => 'Any supported image. A WebP copy is made for the shop, and see-through areas are kept.',

        'link' => 'Written for you from the title: the shop\'s products, searched for the banner\'s name. Change it to point somewhere else.',
    ],

    'columns' => [
        'image' => 'Image',

        'title' => 'Title',

        'link' => 'Link',

        'created_at' => 'Created',
    ],

    'empty_state' => [
        'heading' => 'No banners yet',

        'description' => 'Add a banner to promote an offer on the storefront by clicking the following button.',
    ],
];
