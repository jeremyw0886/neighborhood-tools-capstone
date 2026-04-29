<?php

declare(strict_types=1);

/**
 * Cron: Send overdue borrow notifications.
 *
 * Notifies borrowers with overdue tools (once per day per borrow).
 *
 * Schedule: daily at 8:00 AM
 */

$pdo = require __DIR__ . '/bootstrap.php';

try {
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
