<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Store contact details
     |--------------------------------------------------------------------------
     |
     | The shop's own number and social profiles, written down once so the footer
     | and the contact page cannot drift apart. Each is overridable from the
     | environment, which is where a deployment that reaches a different number
     | would change it.
     |
     */

    'contact' => [
        'phone' => env('STORE_PHONE', '17000000'),
        'phone_href' => env('STORE_PHONE_HREF', 'tel:+97317000000'),
        'instagram_url' => env(
            'STORE_INSTAGRAM_URL',
            'https://www.instagram.com/nexus',
        ),
        'tiktok_url' => env('STORE_TIKTOK_URL', 'https://www.tiktok.com/@nexus'),
    ],
];
