<?php

declare(strict_types=1);

/**
 * Shared cron bootstrap.
 *
 * Asserts CLI invocation, defines BASE_PATH, loads the autoloader and
 * environment, sets the project timezone, caps execution time, and
 * returns a PDO connection via Database::connection().
 *
 * Usage:
 *     $pdo = require __DIR__ . '/bootstrap.php';
 *
 * Scripts that don't need the PDO directly (because they call models
 * which open the connection lazily) can just `require` without capture.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden — cron scripts must run from the command line.\n");
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

date_default_timezone_set('America/New_York');
set_time_limit(300);

return App\Core\Database::connection();
