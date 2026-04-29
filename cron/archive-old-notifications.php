<?php

declare(strict_types=1);

/**
 * Cron: Archive old read notifications (older than 90 days).
 *
 * Schedule: weekly (Sundays at 3:00 AM)
 */

$pdo = require __DIR__ . '/bootstrap.php';

try {
    $stmt = $pdo->prepare('CALL sp_archive_old_notifications(90, @count)');
    $stmt->execute();
    $stmt->closeCursor();

    $count = (int) $pdo->query('SELECT @count')->fetchColumn();

    if ($count > 0) {
        echo '[' . date('Y-m-d H:i:s') . "] Archived {$count} old notification(s)\n";
    }
} catch (Throwable $e) {
    error_log('cron/archive-old-notifications: ' . $e->getMessage());
    exit(1);
}
