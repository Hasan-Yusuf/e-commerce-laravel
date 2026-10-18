<?php

return [
    'title' => 'Dashboard',

    'filters' => [
        'period' => 'Period',
    ],

    'stats' => [
        'revenue' => 'Revenue',
        'revenue_description' => 'Placed in the selected period',

        'orders_to_fulfil' => 'Orders to fulfil',
        'orders_to_fulfil_description' => 'Pending and processing',

        'low_stock' => 'Low-stock products',
        'low_stock_description' => 'At or below the restock threshold',

        'uncollected_payments' => 'Uncollected payments',
        'uncollected_payments_description' => 'Cash on delivery still pending',

        'new_customers' => 'New customers',
        'new_customers_description' => 'Registered in the selected period',

        'unread_messages' => 'Unread messages',
        'unread_messages_description' => 'Contact messages waiting to be read',
    ],

    'chart' => [
        'heading' => 'Revenue trend',
        'description' => 'Daily revenue over the last 30 days',
        'dataset' => 'Revenue',
    ],

    'tables' => [
        'orders_requiring_attention' => 'Orders requiring attention',
        'low_stock_products' => 'Low-stock products',
        'recent_orders' => 'Recent orders',
    ],

    'columns' => [
        'number' => 'Number',

        'customer' => 'Customer',

        'total' => 'Total',

        'placed' => 'Placed',

        'product_name' => 'Product',

        'sku' => 'SKU',

        'available' => 'Available',

        'threshold' => 'Threshold',

        'status' => 'Status',
    ],
];
