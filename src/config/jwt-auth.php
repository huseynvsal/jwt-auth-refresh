<?php

return [
    'secret_key' => env('JWT_SECRET_KEY', 'your-access-secret-key'),
    'refresh_secret_key' => env('JWT_REFRESH_SECRET_KEY', 'your-refresh-secret-key'),
    'access_token_expiration' => env('JWT_ACCESS_TOKEN_EXPIRATION', 3600),
    'refresh_token_expiration' => env('JWT_REFRESH_TOKEN_EXPIRATION', 604800)
];
