<?php

declare(strict_types=1);

/**
 * Cron: Capture daily platform statistics.
 *
 * Schedule: daily at midnight
 */

$pdo = require __DIR__ . '/bootstrap.php';

try {
    $stmt = $pdo->prepare('CALL sp_refresh_platform_daily_stat()');
    $stmt->execute();
    $stmt->closeCursor();

    echo '[' . date('Y-m-d H:i:s') . "] Captured daily platform stats\n";
} catch (Throwable $e) {
    error_log('cron/daily-platform-stats: ' . $e->getMessage());
    exit(1);
}
