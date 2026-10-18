<?php

return [
    'labels' => [
        'model_name' => 'Customer',

        'plural_model_name' => 'Customers',
    ],

    'columns' => [
        'name' => 'Name',
        'email' => 'Email',
        'status' => 'Status',
        'order_count' => 'Order Count',
        'profile_country' => 'Country',
        'phone_number' => 'Phone Number',
        'verified' => 'Verified',
        'address' => 'Address',
    ],

    'infolists' => [
        'sections' => [
            'profile' => 'Profile',

            'contact' => 'Contact',

            'location' => 'Location',
        ],

        'name' => 'Name',

        'email' => 'Email',
        'profile_country' => 'Country',
        'phone_number' => 'Phone Number',

        'email_verified_at' => 'Verified Email',

        'status' => 'Status',

        'country' => 'Country',

        'address' => 'Address',

        'created_at' => 'Joined',
    ],

    'fields' => [
        'status' => 'Status',

        'locale' => 'Language',
    ],

    'filters' => [
        'status' => 'Status',

        'has_orders' => 'Has orders',
    ],

    'actions' => [
        'activate' => 'Activate',

        'deactivate' => 'Deactivate',
    ],

    'notifications' => [
        'activated' => 'Customer activated',

        'deactivated' => 'Customer deactivated',
    ],

    'empty_state' => [
        'heading' => 'No customers yet',

        'description' => 'Customers appear here once they register.',
    ],
];
