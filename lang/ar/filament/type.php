<?php

return [
    'labels' => [
        'navigation' => 'أنواع المنتجات',

        'model_name' => 'نوع',

        'plural_model_name' => 'أنواع المنتجات',

        'update_products' => 'تحديث المنتجات',
    ],

    'fields' => [
        'name' => 'الأسم',

        'category' => 'الفئة',

        'specifications' => 'المواصفات',
        'spec_name_en' => 'الأسم ( إنج )',
        'spec_name_ar' => 'الأسم ( عربي )',
        'spec_value_type' => 'نوع القيمة',
        'spec_unit' => 'الوحدة',
        'spec_options' => 'الخيارات',
        'spec_option_en' => 'الخيار ( إنج )',
        'spec_option_ar' => 'الخيار ( عربي )',
        'spec_values' => 'القيم',
        'spec_value' => 'القيمة',
        'spec_is_filterable' => 'قابل للتصفية',
        'spec_filter_type' => 'نوع التصفية',
    ],

    'placeholders' => [
        'name' => 'هواتف ذكية',

        'spec_name_en' => 'Storage',
        'spec_name_ar' => 'التخزين',

        'spec_unit' => 'جيجابايت',
    ],

    'columns' => [
        'name' => 'الأسم',

        'category' => 'الفئة',

        'specs' => 'المواصفات',

        'specs_need_review' => 'منتجات بانتظار التحديث',
    ],

    'filters' => [
        'specs' => 'المواصفات',
    ],

    'groups' => [
        'category' => 'الفئة',

        'errors' => [
            'no_category' => 'بلا فئة',
        ],
    ],

    'actions' => [
        'add_spec' => 'إضافة مواصفة',

        'add_option' => 'إضافة خيار',

        'add_value' => 'إضافة قيمة',

        'update_products' => 'تحديث المنتجات',

        'update_products_tooltip' => 'يفتح منتجات هذا النوع لإعادة بناء قيمها من المواصفات التي تغيّرت.',

        'save_products' => 'حفظ المنتجات',

        'save_products_tooltip' => 'يعيد بناء المنتجات من القيم التي أدخلتها وينهي العملية.',
    ],

    'helpers' => [
        'spec_values' => 'أدخل هذه القيم عندما يأخذ الرقم مجموعة ثابتة من القيم، مثل أحجام التخزين في الهاتف (64، 128، 256): عندها تُختار القيمة ولا تُكتب، وتُصفى في المتجر كقائمة لا كنطاق.',

        'update_products' => 'تغيّرت مواصفات هذا النوع، ولم تعد منتجاته مطابقة لها. راجع كل منتج أدناه — الحفظ يعيد بناء قيمه من المواصفات باللغتين، ويحذف ما كانت تحمله مواصفة محذوفة.',

        'specs_need_review' => 'تغيّرت المواصفات بعد إدخال بيانات هذه المنتجات.',
    ],

    'notifications' => [
        'products_need_review' => [
            'title' => 'تغيّرت المواصفات',

            'body' => 'راجع قيم منتجات هذا النوع قبل الاعتماد عليها.',
        ],

        'products_updated' => [
            'title' => 'تم تحديث المنتجات',

            'body' => 'كل منتج تحت هذا النوع أصبح مطابقًا لمواصفاته.',
        ],
    ],

    'validation' => [
        'range_requires_number' => 'يمكن استخدام تصفية النطاق مع نوع القيمة الرقمية فقط.',
    ],

    'empty_state' => [
        'heading' => 'لا توجد أنواع بعد',

        'description' => 'أضف نوعًا لتحديد مواصفات المنتج بالضغط على الزر التالي.',
    ],
];
