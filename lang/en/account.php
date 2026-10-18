<?php

return [
    'title' => 'My account',
    'subtitle' => 'My details, and everything I have ordered.',

    'nav' => [
        'label' => 'Account sections',
        'overview' => 'Overview',
        'orders' => 'My Orders',
        'details' => 'My Details',
    ],

    'details' => [
        'title' => 'My details',
        'name' => 'Name',
        'email' => 'Email',
        'language' => 'Preferred language',
        'member_since' => 'Member since',
    ],

    'orders' => [
        'title' => 'Recent orders',
        'view_all' => 'View all orders',

        'empty' => [
            'title' => 'I have not placed an order yet',
            'message' => 'My recent orders will show up here.',
            'action' => 'Continue shopping',
        ],
    ],

    'profile' => [
        'title' => 'My details',
        'subtitle' => 'My name, my contact details, and where my orders are delivered.',
        'breadcrumb' => 'My details',
        'edit' => 'Edit',
        'cancel' => 'Cancel',
        'save' => 'Save changes',
        'saved' => 'My details have been saved.',
        'password_saved' => 'My password has been changed.',

        'details' => [
            'title' => 'My details',
            'name' => 'Full name',
            'email' => 'Email address',
            'language' => 'Preferred language',
            'country' => 'Country',
            'country_placeholder' => 'Select a country',
            'phone_number' => 'Phone number',
        ],

        'address' => [
            'title' => 'Delivery address',
            'hint' => 'The address my orders are delivered to.',
            'saved' => 'My delivery address has been saved.',
        ],

        'password' => [
            'title' => 'Password',
            'current' => 'Current password',
            'new' => 'New password',
            'confirmation' => 'Confirm new password',
            'submit' => 'Change password',
        ],
    ],
];
