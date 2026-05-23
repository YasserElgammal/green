<?php

/**
 * Connect Configuration
 *
 * Connect is Green's outgoing HTTP client subsystem. Use it to call
 * external APIs such as payment gateways, CRMs, messaging providers,
 * shipping APIs, ERPs, and webhooks.
 *
 * @see \YasserElgammal\Green\Connect\Connect
 * @see \YasserElgammal\Green\Connect\ConnectManager
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | This connection is used when calling connect()->get(), connect()->post(),
    | and other methods without explicitly selecting a named connection.
    |
    */
    'default' => $_ENV['CONNECT_DEFAULT'] ?? 'default',

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | Each connection describes one external API/service. Keep credentials in
    | .env and reference them here so application code can stay clean.
    |
    */
    'connections' => [

        'default' => [
            'driver' => 'symfony',
            'base_url' => $_ENV['CONNECT_BASE_URL'] ?? '',
            'timeout' => (float) ($_ENV['CONNECT_TIMEOUT'] ?? 10),
            'connect_timeout' => (float) ($_ENV['CONNECT_CONNECT_TIMEOUT'] ?? 5),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ],

        /*
        'payments' => [
            'driver' => 'symfony',
            'base_url' => $_ENV['PAYMENTS_BASE_URL'] ?? 'https://api.example.com',
            'timeout' => 15,
            'connect_timeout' => 5,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ],
        */

    ],

];
