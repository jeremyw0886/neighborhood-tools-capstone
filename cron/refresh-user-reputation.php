<?php

declare(strict_types=1);

/**
 * Cron: Refresh user reputation materialized view.
 *
 * Schedule: every 4 hours
 */

$pdo = require __DIR__ . '/bootstrap.php';

try {
    $stmt = $pdo->prepare('CALL sp_refresh_user_reputation()');
    $stmt->execute();
    $stmt->closeCursor();

    echo '[' . date('Y-m-d H:i:s') . "] Refreshed user reputation\n";
} catch (Throwable $e) {
    error_log('cron/refresh-user-reputation: ' . $e->getMessage());
    exit(1);
}
