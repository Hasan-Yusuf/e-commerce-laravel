<?php

return [
    'labels' => [
        'model_name' => 'بنر',
        'plural_model_name' => 'بنرات',
    ],

    'fields' => [
        'image' => 'الصورة',

        'title_en' => 'العنوان ( إنج )',
        'title_ar' => 'العنوان ( عربي )',

        'link' => 'الرابط',
    ],

    'placeholders' => [
        'title_en' => 'Enter title',
        'title_ar' => 'أدخل العنوان',

        'link' => 'www.example.com/example',
    ],

    'helpers' => [
        'image' => 'أي صورة مدعومة. يتم إنشاء نسخة WebP للمتجر مع الحفاظ على الخلفية الشفافة.',

        'link' => 'يُكتب تلقائيًا من العنوان: بحث في منتجات المتجر باسم البانر. عدّله إن أردت توجيهه إلى مكان آخر.',
    ],

    'columns' => [
        'image' => 'الصورة',

        'title' => 'العنوان',

        'link' => 'الرابط',

        'created_at' => 'أُنشئ',
    ],

    'empty_state' => [
        'heading' => 'لا توجد لافتات بعد',

        'description' => 'أضف لافتة للترويج لعرض على المتجر بالضغط على الزر التالي.',
    ],
];
