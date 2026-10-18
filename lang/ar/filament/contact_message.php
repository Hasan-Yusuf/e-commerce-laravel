<?php

return [
    'labels' => [
        'model_name' => 'رسالة تواصل',

        'plural_model_name' => 'رسائل التواصل',
    ],

    'columns' => [
        'name' => 'الاسم',

        'email' => 'الإيميل',

        'subject' => 'الموضوع',

        'read_at' => 'مقروءة',

        'created_at' => 'استُلمت',
    ],

    'filters' => [
        'unread' => 'غير المقروءة فقط',
    ],

    'actions' => [
        'mark_read' => 'تحديد كمقروءة',

        'mark_read_tooltip' => 'يسجل أنه تم التعامل مع هذه الرسالة.',
    ],

    'notifications' => [
        'marked_read' => 'تم تحديد الرسالة كمقروءة',
    ],

    'sections' => [
        'sender' => 'المرسل',

        'message' => 'الرسالة',
    ],

    'infolists' => [
        'name' => 'الاسم',

        'email' => 'الإيميل',

        'subject' => 'الموضوع',

        'read_at' => 'قُرئت في',

        'message' => 'الرسالة',
    ],

    'empty_state' => [
        'heading' => 'لا توجد رسائل بعد',

        'description' => 'ستظهر رسائل نموذج التواصل هنا.',
    ],
];
