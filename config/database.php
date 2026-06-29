<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work.
    |
    */

    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    |
    */

    'connections' => [

        'mysql' => [
            'driver'   => 'pdo_mysql',
            'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
            'dbname'   => $_ENV['DB_NAME'] ?? 'green_framework',
            'user'     => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset'  => 'utf8mb4',
        ],

        'sqlite' => [
            'driver' => 'pdo_sqlite',
            'path'   => $_ENV['DB_DATABASE'] ?? __DIR__ . '/../storage/database.sqlite',
        ],

    ],

];
