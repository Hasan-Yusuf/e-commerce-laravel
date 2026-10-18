<?php

return [
    'title' => 'لوحة التحكم',

    'filters' => [
        'period' => 'الفترة',
    ],

    'stats' => [
        'revenue' => 'الإيرادات',
        'revenue_description' => 'الطلبات المُنشأة خلال الفترة المحددة',

        'orders_to_fulfil' => 'طلبات بحاجة للتنفيذ',
        'orders_to_fulfil_description' => 'قيد الانتظار وقيد المعالجة',

        'low_stock' => 'منتجات بمخزون منخفض',
        'low_stock_description' => 'عند حد إعادة التخزين أو أقل',

        'uncollected_payments' => 'مدفوعات غير محصلة',
        'uncollected_payments_description' => 'الدفع عند الاستلام لا يزال معلقاً',

        'new_customers' => 'زبائن جدد',
        'new_customers_description' => 'سُجلوا خلال الفترة المحددة',

        'unread_messages' => 'رسائل غير مقروءة',
        'unread_messages_description' => 'رسائل تواصل تنتظر القراءة',
    ],

    'chart' => [
        'heading' => 'اتجاه الإيرادات',
        'description' => 'الإيرادات اليومية خلال آخر 30 يوماً',
        'dataset' => 'الإيرادات',
    ],

    'tables' => [
        'orders_requiring_attention' => 'طلبات تحتاج إلى انتباه',
        'low_stock_products' => 'منتجات بمخزون منخفض',
        'recent_orders' => 'أحدث الطلبات',
    ],

    'columns' => [
        'number' => 'رقم الطلب',

        'customer' => 'الزبون',

        'total' => 'المجموع',

        'placed' => 'تاريخ الطلب',

        'product_name' => 'المنتج',

        'sku' => 'رقم التخزين',

        'available' => 'المتاح',

        'threshold' => 'الحد الأدنى',

        'status' => 'الحالة',
    ],
];
