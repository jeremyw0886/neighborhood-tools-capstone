<?php

declare(strict_types=1);

namespace App\Core;

/**
 * User roles — backed by role_rol lookup table values.
 */
enum Role: string
{
    case Member    = 'member';
    case Admin     = 'admin';
    case SuperAdmin = 'super_admin';

    /**
     * Check whether this role has at least admin-level privileges.
     */
    public function isAdmin(): bool
    {
        return match ($this) {
            self::Admin, self::SuperAdmin => true,
            default => false,
        };
    }
}
