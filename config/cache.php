<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library.
    |
    */

    'default' => $_ENV['CACHE_DRIVER'] ?? 'file',

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers.
    |
    */

    'stores' => [

        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../storage/cache',
        ],

        'redis' => [
            'driver' => 'redis',
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'database' => $_ENV['REDIS_CACHE_DB'] ?? 1,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the Redis cache driver, we might need a prefix for
    | all cache keys to avoid collisions with other applications.
    |
    */

    'prefix' => $_ENV['CACHE_PREFIX'] ?? 'green_cache_',

];
