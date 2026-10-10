<?php

return [
    'secret' => $_ENV['JWT_SECRET'] ?? '',
    'ttl' => (int) ($_ENV['JWT_TTL'] ?? 3600),
];
