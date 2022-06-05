<?php

use \Layerok\TgMall\Classes\Callbacks as C;

return [
    'credentials' => [
        'test_bot_token' => env('TG_MALL_TEST_BOT_TOKEN'),
        'bot_token' => env('TG_MALL_BOT_TOKEN')
    ],
    'settings' => [
        'products' => [
            'per_page' => 10
        ]
    ]
];
