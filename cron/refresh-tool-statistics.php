<?php

declare(strict_types=1);

/**
 * Cron: Refresh tool statistics materialized view.
 *
 * Schedule: every 2 hours
 */

$pdo = require __DIR__ . '/bootstrap.php';

try {
    $stmt = $pdo->prepare('CALL sp_refresh_tool_statistics()');
    $stmt->execute();
    $stmt->closeCursor();

    echo '[' . date('Y-m-d H:i:s') . "] Refreshed tool statistics\n";
} catch (Throwable $e) {
    error_log('cron/refresh-tool-statistics: ' . $e->getMessage());
    exit(1);
}
