<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny accessor for environment-flag checks.
 *
 * Reads `APP_ENV` from the loaded `.env` so callers don't pluck it from
 * `$_ENV` directly; defaults to "production" when the var is missing so a
 * misconfigured deploy fails closed (e.g. Turnstile, debug rendering).
 */
class Environment
{
    /**
     * Whether the current process is running in production.
     */
    public static function isProduction(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'production') === 'production';
    }
}
