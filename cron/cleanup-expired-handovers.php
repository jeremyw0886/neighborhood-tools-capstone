<?php

declare(strict_types=1);

/**
 * Cron: Clean up expired handover verification codes.
 *
 * Schedule: every 1 hour
 */

$pdo = require __DIR__ . '/bootstrap.php';

try {
    $stmt = $pdo->prepare('CALL sp_cleanup_expired_handover_codes(@count)');
    $stmt->execute();
    $stmt->closeCursor();

    $count = (int) $pdo->query('SELECT @count')->fetchColumn();

    if ($count > 0) {
        echo '[' . date('Y-m-d H:i:s') . "] Cleaned up {$count} expired handover(s)\n";
    }
} catch (Throwable $e) {
    error_log('cron/cleanup-expired-handovers: ' . $e->getMessage());
    exit(1);
}
