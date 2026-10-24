<?php

return [
    'verification_key' => $_ENV['JWT_VERIFICATION_KEY'] ?? ($_ENV['JWT_SECRET'] ?? ''),
    'signing_key' => $_ENV['JWT_SIGNING_KEY'] ?? ($_ENV['JWT_SECRET'] ?? null),
    'secret' => $_ENV['JWT_SECRET'] ?? '',
    'ttl' => (int) ($_ENV['JWT_TTL'] ?? 3600),
    'refresh_owner_type' => 'user',
    'refresh_ttl' => (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800),
    'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
    'issuer' => $_ENV['JWT_ISSUER'] ?? null,
    'audience' => $_ENV['JWT_AUDIENCE'] ?? null,
];
