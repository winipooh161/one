<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Your Telegram Bots
    |--------------------------------------------------------------------------
    | You may use multiple bots at once using the manager class. Each bot
    | that you own should be configured here.
    |
    | Here are each of the telegram bots config parameters.
    |
    | Supported Params:
    |
    | - name: The *personal* name you would like to refer to your bot as.
    | - username: Your Bots Username.
    | - token:    Your Bots Token.
    | - commands: (Optional) The Commands that you want to register for this bot.
    | - async:    (Optional) Indicates if the HTTP client should handle
    |             requests asynchronously.
    | - http:     (Optional) Configuration for HTTP client.
    |
    */
    'bots' => [
        'recipe' => [
            'token' => env('TELEGRAM_RECIPE_BOT_TOKEN'),
            'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH', ''),
            'webhook_url' => env('TELEGRAM_RECIPE_WEBHOOK_URL', ''),
            'commands' => [
                //App\Telegram\Commands\StartCommand::class,
                //App\Telegram\Commands\HelpCommand::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Bot
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the bots you wish to use as
    | your default bot for regular use.
    |
    */
    'default' => 'recipe',

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Requests [Optional]
    |--------------------------------------------------------------------------
    |
    | When set to True, All the requests would be made non-blocking (Async).
    |
    | Default: false
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Handler [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom HTTP Client Handler.
    | Should be an instance of \Telegram\Bot\HttpClients\HttpClientInterface
    |
    | Default: GuzzlePHP
    |
    */
    'http_client_handler' => null,

    /*
    |--------------------------------------------------------------------------
    | Base Bot Url [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom Base Bot Url.
    | Should be a local bot api endpoint or a proxy to the telegram api endpoint
    |
    | Default: https://api.telegram.org/bot
    |
    */
    'base_bot_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Resolve Injected Dependencies in Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Using Laravel's IoC container, we can easily type hint dependencies in
    | our command's constructor and have them automatically resolved for us.
    |
    | Default: true
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'resolve_command_dependencies' => true,

    /*
    |--------------------------------------------------------------------------
    | Register Telegram Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use the SDK's built in command handler system,
    | You can register all the commands here.
    |
    | The commands will be registered automatically in the default bot.
    | You can also register commands in a specific bot using `telegram->bot('name')->addCommands()`
    |
    | The command class should extend the \Telegram\Bot\Commands\Command class.
    |
    | Default: The SDK registers, a help command which when a user sends /help
    | will respond with a list of available commands and description.
    |
    */
    'commands' => [
        Telegram\Bot\Commands\HelpCommand::class,
    ],
];
