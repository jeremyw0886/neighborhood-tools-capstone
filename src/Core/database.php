<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $cfg = require BASE_PATH . '/config/database.php';

            try {
                self::$pdo = new PDO($cfg['dsn'], $cfg['username'], $cfg['password'], $cfg['options']);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                require BASE_PATH . '/src/Views/errors/500.php';
                exit;
            }
        }
        return self::$pdo;
    }

    /**
     * Escape SQL LIKE meta-characters so bound values match literally.
     */
    public static function escapeLike(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
    }
}
