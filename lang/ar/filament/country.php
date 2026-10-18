<?php

return [
    'labels' => [
        'model_name' => 'دولة',

        'plural_model_name' => 'الدول',
    ],

    'sections' => [
        'number_information' => 'معلومات الهاتف',
    ],

    'fields' => [
        'name_en' => 'الإسم ( إنج )',
        'name_ar' => 'الإسم ( عربي )',

        'slug' => 'نص الرابط',

        'calling_code' => 'رقم الإتصال',

        'phone_length' => 'طول رقم الهاتف',
    ],

    'placeholders' => [
        'name_en' => 'Bahrain',
        'name_ar' => 'البحرين',

        'calling_code' => '+973',

        'phone_length' => '8',
    ],

    'helpers' => [
        'slug' => 'يُملأ من الإسم الإنجليزي.',
    ],

    'columns' => [
        'name' => 'الإسم',

        'calling_code' => 'رقم الإتصال',

        'phone_length' => 'طول رقم الهاتف',

        'orders_count' => 'عدد الطلبات',
    ],

    'empty_state' => [
        'heading' => 'لا توجد دول بعد',

        'description' => 'أضف الدول التي يشحن إليها زبائنك بالضغط على الزر التالي.',
    ],
];
