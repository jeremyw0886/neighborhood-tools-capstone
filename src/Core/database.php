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
                // Log SQLSTATE only — $e->getMessage() can include the DSN
                // (host, port) and on some auth failures the credentials too.
                error_log('Database connection failed (SQLSTATE ' . $e->getCode() . ')');

                // CLI (cron) path: emit a plain-text error and exit non-zero
                // so cron logs the failure and downstream automation notices.
                if (PHP_SAPI === 'cli') {
                    fwrite(STDERR, "Database connection failed.\n");
                    exit(1);
                }

                http_response_code(500);

                try {
                    require BASE_PATH . '/src/Views/errors/500.php';
                } catch (\Throwable) {
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
                       . '<title>Server Error — NeighborhoodTools</title></head>'
                       . '<body><h1>500</h1><p>The server is temporarily unavailable. '
                       . 'Please try again in a moment.</p></body></html>';
                }
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
