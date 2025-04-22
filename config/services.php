<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    // Обновляем конфигурацию для Telegram API с поддержкой двух ботов
    'telegram' => [
        // Бот для публикации в канал (первоначальный бот)
        'channel' => [
            'bot_token' => env('TELEGRAM_CHANNEL_BOT_TOKEN'),
            'channel_id' => env('TELEGRAM_CHANNEL_ID'),
        ],
        // Бот для поиска рецептов (обновлённый бот)
        'recipe' => [
            'bot_token' => env('TELEGRAM_RECIPE_BOT_TOKEN', ''),
            'webhook_url' => env('TELEGRAM_RECIPE_WEBHOOK_URL', ''),
            'debug' => env('TELEGRAM_DEBUG', false),
        ],
    ],
    
    // Конфигурация для VK API
    'vk' => [
        'access_token' => env('VK_ACCESS_TOKEN'),
        'api_version' => env('VK_API_VERSION', '5.131'),
        'owner_id' => env('VK_OWNER_ID'),
        'client_id' => env('VK_CLIENT_ID'),
        'client_secret' => env('VK_CLIENT_SECRET'),
        'redirect_uri' => env('VK_REDIRECT_URI'),
    ],

    'zen' => [
        'token' => env('ZEN_API_TOKEN'),
        'channel_id' => env('ZEN_CHANNEL_ID'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
    ],

];
