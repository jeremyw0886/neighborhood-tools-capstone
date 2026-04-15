<?php

declare(strict_types=1);

/**
 * Cron: Refresh tool statistics materialized view.
 *
 * Schedule: every 2 hours
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require BASE_PATH . '/config/database.php';

try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

    $stmt = $pdo->prepare('CALL sp_refresh_tool_statistics()');
    $stmt->execute();
    $stmt->closeCursor();

    echo '[' . date('Y-m-d H:i:s') . "] Refreshed tool statistics\n";
} catch (Throwable $e) {
    error_log('cron/refresh-tool-statistics: ' . $e->getMessage());
    exit(1);
}
