<?php

declare(strict_types=1);

/**
 * Cron: Capture daily platform statistics.
 *
 * Schedule: daily at midnight
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require BASE_PATH . '/config/database.php';

try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

    $stmt = $pdo->prepare('CALL sp_refresh_platform_daily_stat()');
    $stmt->execute();
    $stmt->closeCursor();

    echo '[' . date('Y-m-d H:i:s') . "] Captured daily platform stats\n";
} catch (Throwable $e) {
    error_log('cron/daily-platform-stats: ' . $e->getMessage());
    exit(1);
}
