<?php

return [
    'labels' => [
        'model_name' => 'Contact message',

        'plural_model_name' => 'Contact messages',
    ],

    'columns' => [
        'name' => 'Name',

        'email' => 'Email',

        'subject' => 'Subject',

        'read_at' => 'Read',

        'created_at' => 'Received',
    ],

    'filters' => [
        'unread' => 'Unread only',
    ],

    'actions' => [
        'mark_read' => 'Mark read',

        'mark_read_tooltip' => 'Records that this message has been dealt with.',
    ],

    'notifications' => [
        'marked_read' => 'Message marked as read',
    ],

    'sections' => [
        'sender' => 'Sender',

        'message' => 'Message',
    ],

    'infolists' => [
        'name' => 'Name',

        'email' => 'Email',

        'subject' => 'Subject',

        'read_at' => 'Read at',

        'message' => 'Message',
    ],

    'empty_state' => [
        'heading' => 'No messages yet',

        'description' => 'Contact form submissions appear here.',
    ],
];
