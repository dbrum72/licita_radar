<?php

return [
    'secret' => env('JWT_SECRET'),
    'issuer' => env('JWT_ISSUER', 'licitaradar-api'),
    'audience' => env('JWT_AUDIENCE', 'licitaradar-frontend'),
    'ttl' => (int) env('JWT_TTL', 60),
];
