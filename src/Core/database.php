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
                if (Environment::isProduction()) {
                    error_log('Database connection failed: ' . $e->getMessage());
                    die('Database connection failed.');
                }

                die('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
