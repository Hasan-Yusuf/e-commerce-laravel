<?php

return [
    'steps' => [
        'main' => 'المعلومات الأساسية',

        'configuration' => 'الإعدادات',

        'availability' => 'التوفر',
    ],

    'fields' => [
        'name' => 'الإسم',

        'slug' => 'نص الرابط',

        'desc_en' => 'الوصف ( إنج )',
        'desc_ar' => 'الوصف ( عربي )',

        'price' => 'سعر البيع',

        'cost_price' => 'سعر الشراء',

        'thumbnail' => 'الصورة المصغرة ',

        'images' => 'الصور الإضافية',

        'brand' => 'الشركة',

        'type' => 'النوع',

        'specifications' => 'المواصفات',

        'sku' => 'رقم التخزين',

        'weight' => 'الوزن',

        'stock' => 'الكمية',

        'low_stock_threshold' => 'الحد الادنى للمخزون',

        'featured' => 'إقترح',

        'featured_at' => 'وقت الإقتراح',

        'active' => 'تفعيل',

        'activated_at' => 'وقت التفعيل',

        'reserved_stock' => 'الكمية المحجوزة',

        'available_stock' => 'الكميو المتوفرة',
    ],

    'labels' => [
        'model_name' => 'منتج',

        'plural_model_name' => 'المنتجات',
    ],

    'placeholders' => [
        'name' => 'سماعات لاسلكية',

        'desc_en' => 'Wireless headphones with active noise cancelling...',

        'desc_ar' => 'سماعات لاسلكية مع إلغاء الضوضاء...',

        'price' => '1.000',

        'cost_price' => '0.500',

        'slug' => 'سماعات-لاسلكية',

        'sku' => '[الشركة]-[المنتج]-[الفرعية]',

        'weight' => '1.00',

        'stock' => '10',

        'choose_brand' => 'إختر شركة',

        'choose_type' => 'إختر نوع',
    ],

    'columns' => [
        'name' => 'الأسم',

        'sku' => 'رقم التخزين',

        'price' => 'سعر البيع',

        'cost_price' => 'سعر الشراء',

        'stock' => 'الكمية',

        'reserved_stock' => 'الكمية المحجوزة',

        'available_stock' => 'الكمية المتوفرة',

        'stock_status' => 'حالة المخزون',

        'specs' => 'المواصفات',

        'discount_price' => 'سعر مع التخفيض',

        'status' => 'حالة المنتج',

        'type_name' => 'النوع',

        'category_name' => 'الفئة',

        'featured_at' => 'مقترح',

        'activated_at' => 'مفعل',
    ],

    'groups' => [
        'type' => 'النوع',

        'category' => 'الفئة',

        'brand' => 'الشركة',

        'errors' => [
            'no_type' => 'بلا نوع',

            'no_category' => 'بلا فئة',

            'no_brand' => 'بلا شركة',
        ],
    ],

    'filters' => [
        'type_name' => 'النوع',

        'brand_name' => 'الشركة',

        'stock_status' => 'حالة المخزون',
    ],

    'sections' => [
        'main_info' => 'Main Information',

        'finance_and_stock' => 'Financial & Stock',

        'featured' => [
            'header' => 'الإقتراح',
            'desc' => 'تحديد متى يتم إقتراح المنتج للزبون',
        ],
        'activated' => [
            'header' => 'التفعيل',
            'desc' => 'تحديد متى يستطيع الزبون رؤية المنتج',
        ],
    ],

    'helpers' => [
        'sku_format' => 'رقم التخزين يجب ان يكون مختلفا عن كل فرع و كل منتج. يمكنك استخدام الصيغة التالية: [الشركة]-[المنتج]. مثال: نوفا-سماعات1231',

        'slug' => 'يُملأ من اسم المنتج، وهو عنوان المنتج في المتجر.',

        'image' => 'أي صورة مدعومة. يتم إنشاء نسخة WebP للمتجر مع الحفاظ على الخلفية الشفافة.',

        'low_stock_threshold' => 'عند وصول الكمية الى هذا الرقم، سيتم ارسال اشعار للمسؤولين.',

        'specifications' => 'حقل لكل مواصفة لكل لغة. تظهر الحقول بعد إختيار النوع.',

        'restock_result' => 'المخزون الناتج = المخزون الحالي + الكمية المضافة',
    ],

    'actions' => [
        'duplicate' => 'نسخ',

        'duplicate_tooltip' => 'يفتح نموذج الإنشاء معبأً من بيانات هذا المنتج.',

        'activate' => 'تفعيل',

        'activate_tooltip' => 'يجعل هذا المنتج ظاهراً في المتجر.',

        'activate_selected_tooltip' => 'يجعل كل المنتجات المحددة ظاهرة في المتجر.',

        'deactivate' => 'إلغاء التفعيل',

        'deactivate_tooltip' => 'يخفي هذا المنتج من المتجر دون حذفه.',

        'deactivate_selected_tooltip' => 'يخفي كل المنتجات المحددة من المتجر.',

        'feature' => 'اقتراح',

        'feature_tooltip' => 'يضع هذا المنتج في أقسام المنتجات المقترحة في المتجر.',

        'feature_selected_tooltip' => 'يضع كل المنتجات المحددة في أقسام المنتجات المقترحة.',

        'unfeature' => 'إلغاء الاقتراح',

        'unfeature_tooltip' => 'يزيل هذا المنتج من أقسام المنتجات المقترحة.',

        'unfeature_selected_tooltip' => 'يزيل كل المنتجات المحددة من أقسام المنتجات المقترحة.',

        'restock' => 'زيادة المخزون',

        'restock_tooltip' => 'يضيف الكمية التي تدخلها إلى مخزون هذا المنتج.',

        'restock_quantity' => 'الكمية المضافة',

        'restock_helper' => 'يضيف هذا العدد إلى مخزون المنتج.',

        'restock_result' => 'المخزون الناتج',
    ],

    'notifications' => [
        'activated' => 'تم تفعيل المنتج',

        'deactivated' => 'تم إلغاء تفعيل المنتج',

        'featured' => 'تم اقتراح المنتج',

        'unfeatured' => 'تم إلغاء اقتراح المنتج',

        'restocked' => 'تم تحديث المخزون',
    ],

    'stock_status' => [
        'in_stock' => 'متوفر',

        'low_stock' => 'مخزون منخفض',

        'out_of_stock' => 'غير متوفر',
    ],

    'empty_state' => [
        'heading' => 'لا توجد منتجات بعد',

        'description' => 'أنشئ منتجك الأول لبدء البيع بالضغط على الزر التالي.',
    ],

    'infolists' => [
        'name' => 'إسم المنتج',

        'sku' => 'رقم التخزين',

        'type' => 'نوع المنتج',

        'weight' => 'الوزن',

        'specs' => 'المواصفات',

        'price' => 'سعر البيع',

        'cost_price' => 'سعر الشراء',

        'stock' => 'الكمية',

        'reserved_stock' => 'الكمية المحجوزة',

        'available_stock' => 'الكمية المتوفرة',
    ],
];
