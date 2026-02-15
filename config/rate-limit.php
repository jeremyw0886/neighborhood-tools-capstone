<?php

declare(strict_types=1);

return [
    'login' => [
        'max_attempts'   => 5,
        'window_seconds' => 900,
    ],
    'register' => [
        'max_attempts'   => 3,
        'window_seconds' => 3600,
    ],
    'borrow_request' => [
        'max_attempts'   => 10,
        'window_seconds' => 3600,
    ],
    'forgot_password' => [
        'max_attempts'   => 3,
        'window_seconds' => 900,
    ],
];
