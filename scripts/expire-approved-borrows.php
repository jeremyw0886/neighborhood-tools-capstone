<?php

declare(strict_types=1);

use App\Models\Borrow;
use App\Models\Notification;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

date_default_timezone_set('America/New_York');

$dryRun       = in_array('--dry-run', $argv, true);
$thresholdDays = 7;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--days=')) {
        $thresholdDays = max(1, (int) substr($arg, 7));
    }
}

$stale = Borrow::getStaleApproved($thresholdDays);

if ($stale === []) {
    echo "[" . date('Y-m-d H:i:s') . "] No stale approved borrows found.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($stale) . " stale approved borrow(s).\n";

$cancelled = 0;
$errors    = 0;

foreach ($stale as $row) {
    $borrowId   = (int) $row['id_bor'];
    $toolName   = $row['tool_name_tol'];
    $borrowerId = (int) $row['borrower_id'];
    $lenderId   = (int) $row['lender_id'];
    $daysStale  = (int) $row['days_since_approved'];
    $reason     = "Auto-cancelled: pickup window expired ({$daysStale} days since approval)";

    echo "  Borrow #{$borrowId} \"{$toolName}\" — approved {$daysStale} days ago";

    if ($dryRun) {
        echo " [DRY RUN — skipped]\n";
        continue;
    }

    try {
        $result = Borrow::cancel($borrowId, $lenderId, $reason);

        if (!$result['success']) {
            echo " — FAILED: " . ($result['error'] ?? 'unknown') . "\n";
            error_log("expire-approved: cancel failed for borrow #{$borrowId}: " . ($result['error'] ?? 'unknown'));
            $errors++;
            continue;
        }

        Notification::send(
            $borrowerId,
            'approval',
            'Pickup window expired',
            "Your approved borrow of \"{$toolName}\" was auto-cancelled because "
                . "it was not picked up within {$thresholdDays} days.",
            $borrowId,
        );

        Notification::send(
            $lenderId,
            'approval',
            'Pickup window expired',
            "The approved borrow of \"{$toolName}\" was auto-cancelled because "
                . "the borrower did not pick it up within {$thresholdDays} days. "
                . "Your tool is now available again.",
            $borrowId,
        );

        echo " — cancelled, both parties notified\n";
        $cancelled++;
    } catch (\Throwable $e) {
        echo " — ERROR: " . $e->getMessage() . "\n";
        error_log("expire-approved: exception for borrow #{$borrowId}: " . $e->getMessage());
        $errors++;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Done. Cancelled: {$cancelled}, Errors: {$errors}\n";

exit($errors > 0 ? 1 : 0);
