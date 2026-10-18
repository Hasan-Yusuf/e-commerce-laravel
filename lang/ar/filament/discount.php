<?php

return [
    'labels' => [
        'model_name' => 'تخفيض',
        'plural_model_name' => 'تخفيضات',
    ],

    'sections' => [
        'general_information' => 'معلومات عامة',

        'validity' => 'الصلاحية',

        'apply_to' => 'تخفيضات على',

        'discounted_products' => 'المنتجات المخفضة',

        'discounted_product_types' => 'الأنواع المخفضة',

        'discounted_categories' => 'الفئات المخفضة',

        'discounted_brands' => 'الشركات المخفضة',
    ],

    'fields' => [
        'name' => 'الاسم',

        'type' => 'النوع',

        'value' => 'القيمة',

        'starts_at' => 'يبدأ',

        'ends_at' => 'ينتهي',

        'active' => 'فعال',

        'activated_at' => 'تفعيل',

        'products' => 'المنتجات',

        'categories' => 'الفئات',

        'product_types' => 'أنواع المنتجات',

        'brands' => 'الشركات',
    ],

    'helpers' => [
        'value' => 'النسبة تخصم هذا الجزء من السعر، والمبلغ الثابت يخصم هذا المقدار منه.',
    ],

    'columns' => [
        'name' => 'الاسم',

        'type' => 'النوع',

        'value' => 'القيمة',

        'products_count' => 'منتجات مخفضة',

        'categories_count' => 'فئات المخفضة',

        'types_count' => 'أنواع المخفضة',

        'brands_count' => 'شركات المخفضة',

        'starts_at' => 'يبدأ',

        'ends_at' => 'ينتهي',

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
        'name' => 'الاسم',

        'type' => 'النوع',

        'value' => 'القيمة',

        'starts_at' => 'يبدأ',

        'ends_at' => 'ينتهي',

        'activated_at' => 'يتفعل',
    ],

    'empty_state' => [
        'heading' => 'لا توجد خصومات بعد',

        'description' => 'أضف خصمًا لتنفيذ عرض ترويجي بالضغط على الزر التالي.',
    ],
];
