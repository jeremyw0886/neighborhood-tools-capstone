<?php

declare(strict_types=1);

/**
 * Cron: Refresh user reputation materialized view.
 *
 * Schedule: every 4 hours
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require BASE_PATH . '/config/database.php';

try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

    $stmt = $pdo->prepare('CALL sp_refresh_user_reputation()');
    $stmt->execute();
    $stmt->closeCursor();

    echo '[' . date('Y-m-d H:i:s') . "] Refreshed user reputation\n";
} catch (Throwable $e) {
    error_log('cron/refresh-user-reputation: ' . $e->getMessage());
    exit(1);
}
