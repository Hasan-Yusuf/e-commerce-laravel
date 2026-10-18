<?php

return [
    'title' => 'سلة التسوق',
    'count' => ':count عنصر|:count عناصر',

    'empty' => [
        'title' => 'سلتي فارغة',
        'message' => 'تصفّح منتجاتنا وأضف ما يعجبك.',
        'action' => 'متابعة التسوق',
    ],

    'item' => [
        'image' => 'الصورة',
        'product' => 'المنتج',
        'unit_price' => 'سعر الوحدة',
        'quantity' => 'الكمية',
        'line_total' => 'الإجمالي',
        'action' => 'الإجراءات',
        'unavailable_product' => 'هذا المنتج لم يعد متوفراً.',
        'inactive_product' => 'هذا المنتج لم يعد متاحاً للشراء.',
        'out_of_stock' => 'هذا المنتج غير متوفر.',
        'exceeds_stock' => 'بقي :count فقط في المخزون.',
        'price_changed' => 'تغيّر السعر من :old إلى :new.',
    ],

    'actions' => [
        'update' => 'تحديث',
        'remove' => 'إزالة',
        'move_to_wishlist' => 'انقل إلى المفضلة',
        'move_all_to_wishlist' => 'انقل الكل إلى المفضلة',
    ],

    'summary' => [
        'title' => 'ملخص الطلب',
        'subtotal' => 'المجموع الفرعي',
        'discount' => 'الخصم',
        'total' => 'الإجمالي',
        'coupon' => 'رمز القسيمة',
        'coupon_placeholder' => 'قريباً',
        'coupon_coming_soon' => 'القسائم قادمة قريباً.',
        'checkout' => 'المتابعة إلى الدفع',
    ],

    'status' => [
        'added' => 'أُضيف إلى السلة.',
        'updated' => 'تم تحديث السلة.',
        'removed' => 'تمت إزالة العنصر من السلة.',
        'unavailable' => 'هذا المنتج غير متوفر.',
        'clamped' => 'المتوفر :count فقط؛ تم تعديل سلتي.',
    ],

    'notify' => [
        'added_title' => 'تمت إضافة :name إلى السلة بنجاح',
        'added_body' => 'تمت إضافة :count :name إلى السلة.',
        'clamped_title' => 'تم تعديل الكمية',
        'clamped_body' => 'المتوفر :count فقط من :name؛ تم تعديل سلتي.',
        'unavailable_title' => 'المنتج غير متوفر',
        'unavailable_body' => ':name غير متوفر.',
        'updated_title' => 'تم تحديث السلة',
        'updated_body' => 'تم تعيين الكمية إلى :count.',
        'removed_title' => 'تمت إزالة العنصر من السلة',
    ],
];
