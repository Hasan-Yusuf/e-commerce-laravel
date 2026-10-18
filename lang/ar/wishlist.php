<?php

return [
    'title' => 'قائمة المفضلة',
    'count' => ':count عنصر|:count عناصر',

    'empty' => [
        'message' => 'المنتجات التي أحفظها بأيقونة القلب تظهر هنا، جاهزة للنقل إلى سلتي.',
    ],

    'item' => [
        'unavailable_product' => 'هذا المنتج لم يعد متوفراً.',
        'inactive_product' => 'هذا المنتج لم يعد متاحاً للشراء.',
        'out_of_stock' => 'هذا المنتج غير متوفر.',
    ],

    'actions' => [
        'add' => 'أضف إلى المفضلة',
        'remove' => 'إزالة من المفضلة',
        'move_to_cart' => 'انقل إلى السلة',
        'move_all_to_cart' => 'انقل الكل إلى السلة',
    ],

    'notify' => [
        'added_title' => 'تم حفظ :name في المفضلة',
        'added_body' => 'يمكنني العثور عليه في المفضلة في أي وقت.',
        'exists_title' => 'موجود في المفضلة بالفعل',
        'exists_body' => ':name محفوظ في المفضلة بالفعل.',
        'removed_title' => 'تمت إزالة العنصر من المفضلة',
        'moved_to_cart_title' => 'تم نقل :name إلى السلة',
        'moved_to_cart_body' => ':count :name في سلتي الآن.',
        'moved_to_cart_clamped_title' => 'المتوفر :count فقط',
        'moved_to_cart_clamped_body' => 'المتوفر :count فقط من :name؛ تم تعديل سلتي.',
        'moved_to_cart_unavailable_title' => 'المنتج غير متوفر',
        'moved_to_cart_unavailable_body' => ':name غير متوفر، لذلك بقي في المفضلة.',
        'moved_from_cart_title' => 'تم النقل إلى المفضلة',
        'moved_from_cart_body' => 'تم نقل :name من سلتي إلى المفضلة.',
        'already_wishlisted_body' => ':name كان محفوظاً بالفعل؛ تمت إزالته من سلتي.',
        'moved_from_cart_failed_title' => 'تعذّر النقل إلى المفضلة',
        'moved_from_cart_failed_body' => 'هذا المنتج لم يعد متوفراً.',
        'moved_all_from_cart_title' => 'تم نقل السلة إلى المفضلة',
        'moved_all_from_cart_body' => 'تم نقل :count عنصر إلى المفضلة|تم نقل :count عناصر إلى المفضلة',
        'moved_all_from_cart_kept' => ':count عنصر لم يعد متوفراً وبقي في سلتي.|:count عناصر لم تعد متوفرة وبقيت في سلتي.',
        'moved_all_to_cart_title' => 'تم نقل المفضلة إلى السلة',
        'moved_all_to_cart_body' => 'تم نقل :count عنصر إلى السلة|تم نقل :count عناصر إلى السلة',
        'moved_all_to_cart_clamped' => ':count عنصر تم تحديده حسب المخزون المتوفر.|:count عناصر تم تحديدها حسب المخزون المتوفر.',
        'moved_all_to_cart_unavailable' => ':count عنصر لم يعد متوفراً وبقي في المفضلة.|:count عناصر لم تعد متوفرة وبقيت في المفضلة.',
        'nothing_to_move_title' => 'لا يوجد ما يمكن نقله',
        'nothing_moved_title' => 'تعذّر نقل أي عنصر',
    ],
];
