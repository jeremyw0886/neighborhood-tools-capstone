<?php

declare(strict_types=1);

/**
 * Cron: Send overdue borrow notifications.
 *
 * Notifies borrowers with overdue tools (once per day per borrow).
 *
 * Schedule: daily at 8:00 AM
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require BASE_PATH . '/config/database.php';

try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

    $stmt = $pdo->prepare('CALL sp_send_overdue_notifications(@count)');
    $stmt->execute();
    $stmt->closeCursor();

    $count = (int) $pdo->query('SELECT @count')->fetchColumn();

    if ($count > 0) {
        echo '[' . date('Y-m-d H:i:s') . "] Sent {$count} overdue notification(s)\n";
    }
} catch (Throwable $e) {
    error_log('cron/send-overdue-notifications: ' . $e->getMessage());
    exit(1);
}
