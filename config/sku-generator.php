<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Required Configuration
    |--------------------------------------------------------------------------
    |
    | Core settings required for SKU generation:
    | - prefix: The prefix added to all SKUs (e.g., 'TM' for 'TradeMark')
    | - ulid_length: Length of the unique identifier portion
    | - separator: Character used to separate SKU parts
    |
    */
    'prefix' => 'TM',
    'ulid_length' => 8,
    'separator' => '-',

    /*
    |--------------------------------------------------------------------------
    | Model Mappings
    |--------------------------------------------------------------------------
    |
    | Map your model classes to their SKU type ('product' or 'variant').
    | This tells the generator how to handle SKU generation for each model.
    |
    | Example:
    | \App\Models\Product::class => 'product'
    | \App\Models\ProductVariant::class => 'variant'
    |
    */
    'models' => [
        // \App\Models\Product::class => 'product',
        // \App\Models\ProductVariant::class => 'variant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for how to access and use category information:
    | - accessor: Method/property name to get the category (e.g., 'category')
    | - field: The category model field to use for the SKU (e.g., 'name')
    | - length: How many characters of the category to use
    | - has_many: Whether products can belong to multiple categories
    |
    | Example SKU with category: TM-SHR-12345678 (for 'Shirts' category)
    |
    */
    'category' => [
        'accessor' => 'category',    // Method to access the category
        'field' => 'name',           // Field to use for category name
        'length' => 3,               // Length of the category code
        'has_many' => false,         // Set to true if the model has many categories
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Configuration
    |--------------------------------------------------------------------------
    |
    | Additional settings for variant SKUs and custom modifications:
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Property Values Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for variant property values (like color, size):
    | - accessor: Method to get property values (e.g., 'values')
    | - field: Field name containing the value (e.g., 'title')
    | - length: How many characters of each value to use
    |
    | Example variant SKU: TM-SHR-12345678-RED-LRG
    |
    */
    'property_values' => [
        'accessor' => 'values',      // Method to access the property values
        'field' => 'title',         // Field to use for property value name
        'length' => 3,              // Length of the property value code
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Suffix
    |--------------------------------------------------------------------------
    |
    | Optional callable to add custom suffixes to SKUs.
    | Return null for no suffix, or a string to append to the SKU.
    |
    | Example:
    | 'custom_suffix' => fn($model) => $model->country_code
    |
    */
    'custom_suffix' => null,

    /*
    |--------------------------------------------------------------------------
    | SKU History & Audit Trail
    |--------------------------------------------------------------------------
    |
    | Configuration for tracking SKU lifecycle events:
    | - enabled: Enable/disable history logging
    | - track_user: Track the authenticated user who made changes
    | - track_ip: Track the IP address of the user
    | - track_user_agent: Track the user agent string
    | - retention_days: How long to keep history (null = forever)
    | - table_name: Database table name for history records
    |
    */
    'history' => [
        'enabled' => env('SKU_HISTORY_ENABLED', true),
        'track_user' => true,
        'track_ip' => false,
        'track_user_agent' => false,
        'retention_days' => null, // null = keep forever, int = days to retain
        'table_name' => 'sku_histories',
    ],
];
