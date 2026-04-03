<?php

declare(strict_types=1);

/**
 * Cron: Expire stale approved borrows.
 *
 * Sends a 48-hour warning notification, then auto-cancels approved
 * borrows that haven't completed pickup within 72 hours. Cleans up
 * pending deposits for expired borrows.
 *
 * SiteGround cron command:
 *   /usr/local/bin/php /home/<user>/public_html/cron/expire-stale-borrows.php
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

    $stmt = $pdo->prepare('CALL sp_expire_stale_approved_borrows(@warned, @expired)');
    $stmt->execute();
    $stmt->closeCursor();

    $result = $pdo->query('SELECT @warned AS warned, @expired AS expired')->fetch();

    $warned  = (int) ($result['warned'] ?? 0);
    $expired = (int) ($result['expired'] ?? 0);

    $timestamp = date('Y-m-d H:i:s');

    if ($warned > 0 || $expired > 0) {
        echo "[{$timestamp}] Warned: {$warned}, Expired: {$expired}\n";
    }
} catch (Throwable $e) {
    error_log('cron/expire-stale-borrows: ' . $e->getMessage());
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
