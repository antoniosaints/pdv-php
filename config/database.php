<?php

declare(strict_types=1);

use Pdv\Support\Env;

return [
    'default' => Env::get('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => Env::path($rootPath, 'DB_DATABASE', 'storage/database/pdv.sqlite'),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', '3306'),
            'database' => Env::get('DB_DATABASE', 'pdv'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
        ],
    ],
];
