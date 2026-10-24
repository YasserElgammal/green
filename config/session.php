<?php

$production = ($_ENV['APP_ENV'] ?? 'local') === 'production';

return [
    'cookie_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 0),
    'cookie_path' => $_ENV['SESSION_PATH'] ?? '/',
    'cookie_domain' => ($_ENV['SESSION_DOMAIN'] ?? '') ?: null,
    'cookie_secure' => filter_var(
        $_ENV['SESSION_SECURE_COOKIE'] ?? ($production ? 'true' : 'false'),
        FILTER_VALIDATE_BOOL,
    ),
    'cookie_httponly' => true,
    'cookie_samesite' => $_ENV['SESSION_SAME_SITE'] ?? 'Lax',
];
