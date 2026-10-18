<?php

return [
    'sections' => [
        'customer' => 'Customer Information',

        'customer_contact' => 'Customer Contact',

        'order' => 'Order Details',

        'customer_and_order_status' => 'Customer & Order Status',

        'additional_information' => 'Additional Information',

        'shipping_and_billing' => 'Shipping & Billing',

        'payment_summary' => 'Payment Summary',

        'payments' => 'Payments',

        'order_items' => 'Order Items',
    ],

    'labels' => [
        'model_name' => 'Order',

        'plural_model_name' => 'Orders',

        'order_item' => 'Order Item',

        'order_items' => 'Order Items',

        'add_order_item' => 'Add Item',
    ],

    'fields' => [
        'user' => 'Customer',

        'number' => 'Number',

        'status' => 'Status',

        'delivery_method' => 'Delivery method',

        'paid_at' => 'Paid',

        'orderItems' => 'Order Items',

        'product' => 'Product',

        'sku' => 'SKU ( Stock Keeping Unit )',

        'quantity' => 'Quantity',

        'unit_price' => 'Unit Price',

        'total_price' => 'Total Price',

        'notes' => 'Notes',

        'shipping_and_billing_same' => 'Same shipping and billing address',

        'shipping_address' => 'Shipping Address',

        'billing_address' => 'Billing Address',

        'subtotal' => 'SubTotal',

        'tax_percentage' => 'Tax Percentage',

        'tax_amount' => 'Tax Amount',

        'shipping_amount' => 'Shipping Amount',

        'discount_amount' => 'Discount Amount',

        'total_amount' => 'Total Amount',
    ],

    'hints' => [
        'quantity' => 'Available stock: :value',
    ],

    'helpers' => [
        'number' => 'Assigned automatically when the order is saved.',

        'tax_percentage' => 'A share of the subtotal, added back to the total.',
    ],

    'tabs' => [
        'all' => 'All',
    ],

    'copy_messages' => [
        'number' => 'Number Copyied',
    ],

    'columns' => [
        'number' => 'Number',

        'user' => 'Customer',

        'status' => 'Status',

        'delivery_method' => 'Delivery method',

        'subtotal' => 'Subtotal',

        'tax_amount' => 'Tax Amount',

        'shipping_address' => 'Shipping Address',

        'country' => 'Country',

        'created_at' => 'Ordered',

        'shipping_amount' => 'Shipping Amount',

        'discount_amount' => 'Discount Amount',

        'coupon_code' => 'Coupon Code',

        'total_amount' => 'Total Amount',

        'notes' => 'Notes',

        'paid_at' => 'Paid',

        'payment_status' => 'Payment Status',

        'order_items_count' => 'Items Count',
    ],

    'groups' => [
        'status' => 'Status',
        'country' => 'Country',
    ],

    'info' => [
        'customer_name' => 'Customer',

        'customer_email' => 'Customer Email',

        'customer_shipping_address' => 'Shipping Address',

        'customer_billing_address' => 'Billing Address',

        'customer_status' => 'Behaviuor',

        'country' => 'Country',

        'phone_number' => 'Phone Number',

        'subtotal' => 'Original Price',

        'tax' => 'Tax',

        'discount' => 'Discount',

        'coupon_code' => 'Coupon Code',

        'total' => 'Total',

        'status' => 'Order Status',

        'created_at' => 'Purchased',

        'order_number' => 'Order Number',

        'order_items' => 'Oreded Items',

        'product_name' => 'Product Name',

        'product_sku' => 'Product SKU ( Stock Keeping Unit )',

        'quantity' => 'Quantity',

        'unit_price' => 'Unit Price',

        'total_price' => 'Total Price',

        'payment_method' => 'Method',

        'payment_status' => 'Status',

        'payment_amount' => 'Amount',

        'payment_paid_at' => 'Paid at',
    ],

    'filters' => [
        'created_at' => 'Ordered',

        'from' => 'From',

        'until' => 'Until',

        'status' => 'Status',

        'delivery_method' => 'Delivery method',

        'payment_status' => 'Payment status',
    ],

    'actions' => [
        'fulfil' => 'Fulfil',

        'cancel' => 'Cancel',

        'refund' => 'Refund',

        'reinstate' => 'Reinstate',

        'mark_collected' => 'Mark collected',

        'confirm' => 'Confirm',

        'fulfil_tooltip' => 'Marks this order as shipped or delivered and takes its items out of inventory.',

        'cancel_tooltip' => 'Cancels this order and releases the stock it is holding.',

        'refund_tooltip' => 'Refunds this order and returns its items to inventory.',

        'reinstate_tooltip' => 'Reinstates this cancelled or refunded order, reserving its stock again.',

        'confirm_tooltip' => 'Confirms this order and moves it into processing.',

        'mark_collected_tooltip' => "Records the cash collected for this order's pending payment.",
    ],

    'notifications' => [
        'fulfilled' => 'Order fulfilled',

        'cancelled' => 'Order cancelled',

        'refunded' => 'Order refunded',

        'reinstated' => 'Order reinstated',

        'confirmed' => 'Order confirmed',

        'payment_collected' => 'Payment marked as collected',
    ],

    'empty_state' => [
        'heading' => 'No orders yet',

        'description' => 'Orders appear here once a shopper checks out.',
    ],
];
