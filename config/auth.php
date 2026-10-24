<?php

return [
    'jwt' => [
        'enabled' => filter_var($_ENV['AUTH_JWT_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOL),
        'refresh_tokens' => filter_var($_ENV['AUTH_JWT_REFRESH_TOKENS'] ?? 'false', FILTER_VALIDATE_BOOL),
    ],
];
