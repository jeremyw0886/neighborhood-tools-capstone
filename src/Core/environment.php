<?php

declare(strict_types=1);

namespace App\Core;

class Environment
{
    public static function isProduction(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'production') === 'production';
    }
}
