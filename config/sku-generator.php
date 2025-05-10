<?php

return [
    'prefix' => 'TM',
    'product_category_length' => 3,
    'ulid_length' => 8,
    'property_value_length' => 3,

    'separator' => '-',

    'models' => [
        // \App\Models\Product::class => 'product',
        // \App\Models\ProductVariant::class => 'variant',
    ],

    'custom_suffix' => null, // function ($model) {
        // Example: add country code suffix if present
        // return property_exists($model, 'country_code') ? $model->country_code : null;
    // },
];
