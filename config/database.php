<?php
use App\Support\Env;

return [
    'driver' => Env::get('DB_CONNECTION', 'mysql'),
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'nexbus'),
    'username' => Env::get('DB_USERNAME', 'nexbususer'),
    'password' => Env::get('DB_PASSWORD', 'Nexbus@12345'),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
];
