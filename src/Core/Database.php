<?php

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $cfg = require BASE_PATH . '/config/database.php';
            self::$pdo = new PDO($cfg['dsn'], $cfg['username'], $cfg['password'], $cfg['options']);
        }
        return self::$pdo;
    }
}
