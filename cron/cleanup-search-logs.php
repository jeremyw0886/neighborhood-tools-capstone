<?php

declare(strict_types=1);

/**
 * Cron: Clean up search logs older than 30 days.
 *
 * Schedule: weekly (Sundays at 3:00 AM)
 */

$pdo = require __DIR__ . '/bootstrap.php';

try {
    $stmt = $pdo->prepare('CALL sp_cleanup_old_search_logs(30, @count)');
    $stmt->execute();
    $stmt->closeCursor();

    $count = (int) $pdo->query('SELECT @count')->fetchColumn();

    if ($count > 0) {
        echo '[' . date('Y-m-d H:i:s') . "] Cleaned up {$count} old search log(s)\n";
    }
} catch (Throwable $e) {
    error_log('cron/cleanup-search-logs: ' . $e->getMessage());
    exit(1);
}
