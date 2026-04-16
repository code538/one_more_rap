<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shiprocket API
    |--------------------------------------------------------------------------
    |
    | Keep credentials out of code. Configure via .env.
    |
    */
    'base_url' => env('SHIPROCKET_BASE_URL', 'https://apiv2.shiprocket.in/v1/external'),
    'email' => env('SHIPROCKET_EMAIL'),
    'password' => env('SHIPROCKET_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Defaults used when creating shipments
    |--------------------------------------------------------------------------
    */
    'channel_id' => env('SHIPROCKET_CHANNEL_ID'), // optional
    'pickup_location' => env('SHIPROCKET_PICKUP_LOCATION'), // name configured in Shiprocket
    'company_name' => env('SHIPROCKET_COMPANY_NAME'),

    // Default parcel dimensions/weight (override per request if needed)
    'default_weight' => (float) env('SHIPROCKET_DEFAULT_WEIGHT', 0.5),
    'default_length' => (float) env('SHIPROCKET_DEFAULT_LENGTH', 10),
    'default_breadth' => (float) env('SHIPROCKET_DEFAULT_BREADTH', 10),
    'default_height' => (float) env('SHIPROCKET_DEFAULT_HEIGHT', 5),

    // Cache token for N minutes (Shiprocket tokens are long-lived; keep conservative)
    'token_cache_minutes' => (int) env('SHIPROCKET_TOKEN_CACHE_MINUTES', 720),
];

