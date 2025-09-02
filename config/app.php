<?php
use App\Support\Env;

return [
    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */
    'env'   => Env::get('APP_ENV', 'local'),
    'debug' => Env::get('APP_DEBUG', 'true') === 'true',
    'url'   => Env::get('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Security / Sessions
    |--------------------------------------------------------------------------
    */
    'key'     => Env::get('APP_KEY', 'fallback_key'),
    'session' => [
        'driver'   => Env::get('SESSION_DRIVER', 'file'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', 120),
        'name'     => Env::get('SESSION_NAME', 'NEXBUSSESSID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'log' => [
        'channel' => Env::get('LOG_CHANNEL', 'stack'),
        'level'   => Env::get('LOG_LEVEL', 'debug'),
    ],
];
