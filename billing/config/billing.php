<?php

return [
    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', true),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'paypal' => [
        'enabled' => env('PAYPAL_ENABLED', false),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // 'sandbox' or 'live'
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'currency' => env('BILLING_CURRENCY', 'USD'),

    'deployment_tags' => env('BILLING_DEPLOYMENT_TAGS'),
];
