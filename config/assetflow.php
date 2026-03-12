<?php

return [
    'company_name' => env('COMPANY_NAME', 'Your Company'),
    'product_name' => 'AssetFlow',
    'brand_color' => env('ASSETFLOW_BRAND_COLOR', '#1459D9'),
    'label_url' => env('APP_URL', 'http://assetflow.example.local'),
    'metrics_cache_minutes' => (int) env('ASSETFLOW_METRICS_CACHE_MINUTES', 0),
    'defaults' => [
        'email_domain' => env('ASSETFLOW_DEFAULT_EMAIL_DOMAIN', 'example.local'),
        'admin_email' => env('ASSETFLOW_ADMIN_EMAIL', 'admin@example.local'),
        'admin_name' => env('ASSETFLOW_ADMIN_NAME', 'System Administrator'),
        'admin_username' => env('ASSETFLOW_ADMIN_USERNAME', 'admin'),
        'admin_password' => env('ASSETFLOW_ADMIN_PASSWORD', ''),
        'import_default_password' => env('ASSETFLOW_IMPORT_DEFAULT_PASSWORD', ''),
        'demo_password' => env('ASSETFLOW_DEMO_PASSWORD', ''),
        'vendor_contact_email' => env('ASSETFLOW_DEFAULT_VENDOR_EMAIL', 'procurement@example.local'),
    ],
    'receipts' => [
        'email_enabled' => (bool) env('ASSETFLOW_EMAIL_RECEIPTS', false),
        'return_email_enabled' => (bool) env('ASSETFLOW_EMAIL_RETURNS', true),
        'cc_actor' => (bool) env('ASSETFLOW_EMAIL_RECEIPTS_CC_ACTOR', true),
        'fallback_to_actor' => (bool) env('ASSETFLOW_EMAIL_RECEIPTS_FALLBACK_TO_ACTOR', true),
        'bcc' => array_filter(array_map('trim', explode(',', (string) env('ASSETFLOW_EMAIL_RECEIPTS_BCC', '')))),
    ],
];
