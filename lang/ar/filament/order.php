<?php

return [
    'sections' => [
        'customer' => 'معلومات الزبون',

        'customer_contact' => 'معلومات التواصل',

        'order' => 'تفاصيل الطلب',

        'customer_and_order_status' => 'Customer & Order Status',

        'additional_information' => 'Additional Information',

        'shipping_and_billing' => 'Shipping & Billing',

        'payment_summary' => 'Payment Summary',

        'payments' => 'المدفوعات',

        'order_items' => 'المنتجات المطلوبة',
    ],

    'labels' => [
        'model_name' => 'طلب',

        'plural_model_name' => 'طلبات',

        'order_item' => 'المنتج',
        'order_items' => 'المنتجات',

        'add_order_item' => 'اضف منتجا',
    ],

    'fields' => [
        'user' => 'الزبون',

        'number' => 'رقم الطلب',

        'status' => 'حالة الطلب',

        'delivery_method' => 'طريقة التسليم',

        'paid_at' => 'مدفوع',

        'orderItems' => 'المنتجات',

        'product' => 'المنتج',

        'sku' => 'رقم التخزين',

        'quantity' => 'الكمية',

        'unit_price' => 'سعر الوحدة',

        'total_price' => 'السعر الكلي',

        'notes' => 'ملاحظات',

        'shipping_and_billing_same' => 'استخدم عنوان واحد للدفع و التوصيل',

        'shipping_address' => 'عنوان التوصيل',

        'billing_address' => 'عنوان الدفع',

        'subtotal' => 'السعر الاساسي',

        'tax_percentage' => 'نسبة القيمة المضافة',

        'tax_amount' => 'سعر القيمة المضافة',

        'shipping_amount' => 'سعر الشحن',

        'discount_amount' => 'قيمة التخفيض',

        'total_amount' => 'السعر النهائي',
    ],

    'hints' => [
        'quantity' => 'الكمية المتاحة: ',
    ],

    'helpers' => [
        'number' => 'يُعطى تلقائيًا عند حفظ الطلب.',

        'tax_percentage' => 'نسبة من المجموع الفرعي تُضاف إلى الإجمالي.',
    ],

    'tabs' => [
        'all' => 'الكل',
    ],

    'copy_messages' => [
        'number' => 'تم النسخ',
    ],

    'columns' => [
        'number' => 'رقم الطلب',

        'user' => 'الزبون',

        'status' => 'حالة الطلب',

        'delivery_method' => 'طريقة التسليم',

        'subtotal' => 'السعر الأساسي',

        'tax_amount' => 'القيمة المضافة',

        'shipping_address' => 'عنوان التوصيل',

        'country' => 'البلد',

        'created_at' => 'طُلب',

        'shipping_amount' => 'مبلغ التوصيل',

        'discount_amount' => 'سعر الخصم',

        'coupon_code' => 'رمز القسيمة',

        'total_amount' => 'السعر الكلي',

        'notes' => 'ملاحظات',

        'paid_at' => 'دُفع',

        'payment_status' => 'حالة الدفع',

        'order_items_count' => 'عدد المنتجات',
    ],

    'groups' => [
        'status' => 'حالة الطلب',
        'country' => 'البلد',
    ],

    'info' => [
        'customer_name' => 'الزبون',

        'customer_email' => 'إيميل الزبون',

        'customer_shipping_address' => 'عنوان الشحن/التوصيل',

        'customer_billing_address' => 'عنوان الدفع',

        'customer_status' => 'حالة الزبون',

        'country' => 'البلد',

        'phone_number' => 'الهاتف',

        'subtotal' => 'السعر الأصلي',

        'tax' => 'الضريبة',

        'discount' => 'التخفيض',

        'coupon_code' => 'رمز القسيمة',

        'total' => 'المجموع',

        'status' => 'حالة الطلب',

        'created_at' => 'طُلب',

        'order_number' => 'رقم الطلب',

        'order_items' => 'المنتجات',

        'product_name' => 'إسم المنتج',

        'product_sku' => 'رقم التخزين',

        'quantity' => 'الكمية',

        'unit_price' => 'سعر الوحدة',

        'total_price' => 'السعر الكلي',

        'payment_method' => 'الطريقة',

        'payment_status' => 'الحالة',

        'payment_amount' => 'المبلغ',

        'payment_paid_at' => 'دُفع في',
    ],

    'filters' => [
        'created_at' => 'طُلب',

        'from' => 'من',

        'until' => 'إلى',

        'status' => 'حالة الطلب',

        'delivery_method' => 'طريقة التسليم',

        'payment_status' => 'حالة الدفع',
    ],

    'actions' => [
        'fulfil' => 'تنفيذ',

        'cancel' => 'إلغاء',

        'refund' => 'استرداد',

        'reinstate' => 'إعادة تفعيل',

        'mark_collected' => 'تسجيل التحصيل',

        'confirm' => 'تأكيد',

        'fulfil_tooltip' => 'يحدد الطلب كمشحون أو مُسلَّم ويخصم منتجاته من المخزون.',

        'cancel_tooltip' => 'يلغي الطلب ويحرر الكمية المحجوزة من المخزون.',

        'refund_tooltip' => 'يسترد الطلب ويعيد منتجاته إلى المخزون.',

        'reinstate_tooltip' => 'يعيد تفعيل الطلب الملغى أو المسترد ويحجز مخزونه من جديد.',

        'confirm_tooltip' => 'يؤكد الطلب وينقله إلى قيد المعالجة.',

        'mark_collected_tooltip' => 'يسجل المبلغ النقدي المحصل من الزبون لهذا الطلب.',
    ],

    'notifications' => [
        'fulfilled' => 'تم تنفيذ الطلب',

        'cancelled' => 'تم إلغاء الطلب',

        'refunded' => 'تم استرداد الطلب',

        'reinstated' => 'تمت إعادة تفعيل الطلب',

        'confirmed' => 'تم تأكيد الطلب',

        'payment_collected' => 'تم تسجيل الدفع كمحصَّل',
    ],

    'empty_state' => [
        'heading' => 'لا توجد طلبات بعد',

        'description' => 'ستظهر الطلبات هنا بعد إتمام الزبون للشراء.',
    ],
];
