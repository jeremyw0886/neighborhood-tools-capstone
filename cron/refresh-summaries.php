<?php

declare(strict_types=1);

/**
 * Cron: Refresh neighborhood and category summary materialized views.
 *
 * Schedule: every 1 hour
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require BASE_PATH . '/config/database.php';

try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

    $stmt = $pdo->prepare('CALL sp_refresh_neighborhood_summary()');
    $stmt->execute();
    $stmt->closeCursor();

    $stmt = $pdo->prepare('CALL sp_refresh_category_summary()');
    $stmt->execute();
    $stmt->closeCursor();

    echo '[' . date('Y-m-d H:i:s') . "] Refreshed neighborhood and category summaries\n";
} catch (Throwable $e) {
    error_log('cron/refresh-summaries: ' . $e->getMessage());
    exit(1);
}
