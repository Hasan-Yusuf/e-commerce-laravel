<?php

return [
    'labels' => [
        'model_name' => 'الزبون',

        'plural_model_name' => 'الزبائن',
    ],

    'columns' => [
        'name' => 'الأسم',
        'email' => 'الإيميل',
        'status' => 'الحالة',
        'order_count' => 'عدد الطلبات',
        'profile_country' => 'الدولة',
        'phone_number' => 'رقم الهاتف',
        'verified' => 'التحقق',
        'address' => 'العنوان',
    ],

    'infolists' => [
        'sections' => [
            'profile' => 'الملف الشخصي',

            'contact' => 'التواصل',

            'location' => 'الموقع',
        ],

        'name' => 'الأسم',

        'email' => 'الايميل',

        'profile_country' => 'الدولة',
        'phone_number' => 'رقم الهاتف',

        'email_verified_at' => 'متحقق من الايميل',

        'status' => 'حالى الزبون',

        'country' => 'البلد',

        'address' => 'العنوان',

        'created_at' => 'تاريخ التسجيل',
    ],

    'fields' => [
        'status' => 'الحالة',

        'locale' => 'اللغة',
    ],

    'filters' => [
        'status' => 'الحالة',

        'has_orders' => 'لديه طلبات',
    ],

    'actions' => [
        'activate' => 'تفعيل',

        'deactivate' => 'إلغاء التفعيل',
    ],

    'notifications' => [
        'activated' => 'تم تفعيل الزبون',

        'deactivated' => 'تم إلغاء تفعيل الزبون',
    ],

    'empty_state' => [
        'heading' => 'لا يوجد زبائن بعد',

        'description' => 'سيظهر الزبائن هنا بعد التسجيل.',
    ],
];
