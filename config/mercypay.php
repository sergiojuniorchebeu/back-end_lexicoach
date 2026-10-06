<?php

return [
    'base_url' => env('MERCYPAY_BASE_URL', 'https://mercy-pay-ifxw.kennhosting.app'),
    'api_key' => env('MERCYPAY_API_KEY'),
    'webhook_secret' => env('MERCYPAY_WEBHOOK_SECRET'),
    'timeout' => (int) env('MERCYPAY_TIMEOUT', 30),

    'smart_abstract' => [
        'amount' => (int) env('MERCYPAY_SMART_ABSTRACT_AMOUNT', 100),
        'currency' => env('MERCYPAY_SMART_ABSTRACT_CURRENCY', 'XAF'),
    ],
];
