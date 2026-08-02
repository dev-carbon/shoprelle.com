<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot
    |--------------------------------------------------------------------------
    |
    | The token comes from BotFather. The secret is sent back by Telegram on
    | every webhook call as X-Telegram-Bot-Api-Secret-Token, and is the only
    | thing proving a request really came from them.
    |
    | The username is what a human needs: it is the only part of the bot that
    | makes a link. The landing page advertises the channel exactly when this is
    | set, so an unconfigured bot is never offered to a visitor.
    |
    */

    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 10),
        'max_photo_bytes' => (int) env('TELEGRAM_MAX_PHOTO_BYTES', 5 * 1024 * 1024),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
