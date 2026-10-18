<?php

return [
    'labels' => [
        'model_name' => 'قسيمة',
        'plural_model_name' => 'قسائم',
    ],

    'sections' => [
        'general_information' => 'معلومات عامة',

        'validity' => 'الصلاحية',

        'usage' => 'الاستخدام',

        'used_orders' => 'الطلبات المستخدمة لهذه القسيمة',
    ],

    'fields' => [
        'code' => 'الرمز',

        'description' => 'الوصف',

        'type' => 'النوع',

        'value' => 'القيمة',

        'maximum_discount' => 'الحد الأقصى للخصم',

        'min_order_amount' => 'أقل مبلغ للطلب',

        'usage_limit' => 'حد الاستخدام',

        'activated_at' => 'تفعيل',

        'starts_at' => 'يبدأ',

        'expires_at' => 'ينتهي',
    ],

    'helpers' => [
        'value' => 'النسبة تخصم هذا الجزء من المجموع الفرعي، والمبلغ الثابت يخصم هذا المقدار منه.',

        'maximum_discount' => 'أقصى ما يمكن أن تخصمه هذه القسيمة، مهما بلغت القيمة المحسوبة.',

        'usage_limit' => 'اتركه فارغاً لاستخدام غير محدود.',
    ],

    'columns' => [
        'code' => 'الرمز',

        'type' => 'النوع',

        'value' => 'القيمة',

        'used_count' => 'المستخدم',

        'usage_limit' => 'الحد',

        'unlimited' => 'غير محدود',

        'activated_at' => 'تفعيل',

        'starts_at' => 'يبدأ',

        'expires_at' => 'ينتهي',

        'is_currently_active' => 'فعال الآن',
    ],

    'filters' => [
        'type' => 'النوع',

        'is_active' => 'فعال',

        'active' => 'فعال',

        'inactive' => 'غير فعال',

        'active_now' => 'فعال الآن',
    ],

    'infolists' => [
        'code' => 'الرمز',

        'description' => 'الوصف',

        'type' => 'النوع',

        'value' => 'القيمة',

        'maximum_discount' => 'الحد الأقصى للخصم',

        'min_order_amount' => 'أقل مبلغ للطلب',

        'usage_limit' => 'حد الاستخدام',

        'unlimited' => 'غير محدود',

        'used_count' => 'عدد مرات الاستخدام',

        'activated_at' => 'يتفعل',

        'starts_at' => 'يبدأ',

        'expires_at' => 'ينتهي',

        'orders_count' => 'الطلبات',

        'total_discount' => 'إجمالي الخصم الممنوح',
    ],

    'copy_messages' => [
        'code' => 'تم نسخ الرمز',
    ],

    'empty_state' => [
        'heading' => 'لا توجد قسائم بعد',

        'description' => 'أضف قسيمة لتنفيذ عرض ترويجي بالضغط على الزر التالي.',
    ],
];
