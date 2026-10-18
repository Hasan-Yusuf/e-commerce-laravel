<?php

return [
    'steps' => [
        'main' => 'Main Information',

        'configuration' => 'Configuration',

        'availability' => 'Availability',
    ],

    'fields' => [
        'name' => 'Name',

        'slug' => 'Slug',

        'desc_en' => 'Description ( English )',

        'desc_ar' => 'Description ( Arabic )',

        'price' => 'Price',

        'cost_price' => 'Cost',

        'thumbnail' => 'Thumbnail',

        'images' => 'Images',

        'brand' => 'Brand',

        'type' => 'Type',

        'specifications' => 'Specifications',

        'sku' => 'SKU ( Stock Keeping Unit )',

        'weight' => 'Weight',

        'stock' => 'Stock',

        'low_stock_threshold' => 'Min stock threshold',

        'featured' => 'Featured',

        'featured_at' => 'Feature At',

        'active' => 'Active',

        'activated_at' => 'Activate At',

        'reserved_stock' => 'Reserved Stock',

        'available_stock' => 'Available Stock',
    ],

    'labels' => [
        'model_name' => 'Product',

        'plural_model_name' => 'Products',
    ],

    'placeholders' => [
        'name' => 'Wireless Headphones',

        'sku' => 'Nova-Headphones',

        'desc_en' => 'Wireless headphones with active noise cancelling...',

        'desc_ar' => 'Wireless headphones with active noise cancelling...',

        'price' => '1.000',

        'cost_price' => '0.500',

        'slug' => 'wireless-headphones',

        'weight' => 'Weight',

        'stock' => '100',

        'choose_brand' => 'Choose a brand',

        'choose_type' => 'Choose a type',
    ],

    'columns' => [
        'name' => 'Name',

        'sku' => 'SKU',

        'price' => 'Price',

        'cost_price' => 'Cost',

        'stock' => 'Stock',

        'reserved_stock' => 'Reserved Stock',

        'available_stock' => 'Available Stock',

        'stock_status' => 'Stock Status',

        'specs' => 'Specifications',

        'discount_price' => 'Price ( Discount )',

        'status' => 'Status',

        'type_name' => 'Type',

        'category_name' => 'Category',

        'featured_at' => 'Featured',

        'activated_at' => 'Active',
    ],

    'groups' => [
        'type' => 'Type',

        'category' => 'Category',

        'brand' => 'Brand',

        'errors' => [
            'no_type' => 'No Type',

            'no_category' => 'No Category',

            'no_brand' => 'No Brand',
        ],
    ],

    'filters' => [
        'type_name' => 'Type',

        'brand_name' => 'Brand',

        'stock_status' => 'Stock status',
    ],

    'sections' => [
        'main_info' => 'Main Information',

        'finance_and_stock' => 'Finance & Stock',

        'featured' => [
            'header' => 'Featured',
            'desc' => 'Decide when the product is featured',
        ],
        'activated' => [
            'header' => 'Activated',
            'desc' => 'Decide when the product is visible to the customer',
        ],
    ],

    'helpers' => [
        'sku_format' => 'SKU must be unique, so use this format: [Brand]-[Product] e.g. Nova-Headphones',

        'slug' => 'Filled in from the name. It is the address of the product in the shop.',

        'image' => 'Any supported image. A WebP copy is made for the shop, and see-through areas are kept.',

        'low_stock_threshold' => 'If the stock is below this threshold, admins will be notified',

        'specifications' => 'One input per specification per locale. The fields appear after you choose a type.',

        'restock_result' => 'Resulting stock = current stock + new stock',
    ],

    'actions' => [
        'duplicate' => 'Duplicate',

        'duplicate_tooltip' => 'Opens the create form pre-filled from this product.',

        'activate' => 'Activate',

        'activate_tooltip' => 'Makes this product visible in the shop.',

        'activate_selected_tooltip' => 'Makes every selected product visible in the shop.',

        'deactivate' => 'Deactivate',

        'deactivate_tooltip' => 'Hides this product from the shop without deleting it.',

        'deactivate_selected_tooltip' => 'Hides every selected product from the shop.',

        'feature' => 'Feature',

        'feature_tooltip' => 'Puts this product in the featured sections of the storefront.',

        'feature_selected_tooltip' => 'Puts every selected product in the featured sections.',

        'unfeature' => 'Unfeature',

        'unfeature_tooltip' => 'Removes this product from the featured sections.',

        'unfeature_selected_tooltip' => 'Removes every selected product from the featured sections.',

        'restock' => 'Add Stock',

        'restock_tooltip' => 'Adds the quantity you enter to the stock of this product.',

        'restock_quantity' => 'Quantity to add',

        'restock_helper' => 'Adds this many units to the product stock.',

        'restock_result' => 'Resulting stock',
    ],

    'notifications' => [
        'activated' => 'Product activated',

        'deactivated' => 'Product deactivated',

        'featured' => 'Product featured',

        'unfeatured' => 'Product unfeatured',

        'restocked' => 'Stock updated',
    ],

    'stock_status' => [
        'in_stock' => 'In stock',

        'low_stock' => 'Low stock',

        'out_of_stock' => 'Out of stock',
    ],

    'empty_state' => [
        'heading' => 'No products yet',

        'description' => 'Create your first product to start selling by clicking the following button.',
    ],

    'infolists' => [
        'name' => 'Product Name',

        'sku' => 'SKU ( Stock Keeping Unit )',

        'type' => 'Product\'s Type',

        'weight' => 'Weight',

        'specs' => 'Specification',

        'price' => 'Price',

        'cost_price' => 'Cost',

        'stock' => 'Stock',

        'reserved_stock' => 'Reserved Stock',

        'available_stock' => 'Available Stock',
    ],
];
