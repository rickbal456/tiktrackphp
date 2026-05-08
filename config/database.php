<?php

declare(strict_types=1);

return [
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => (int) env('DB_PORT', '3308'),
    'database' => env('DB_NAME', 'tiktrack_php'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASSWORD', 'root123'),
    'charset' => 'utf8mb4',
];
