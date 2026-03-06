<?php

declare(strict_types=1);

use App\Models\Borrow;
use App\Models\Notification;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

date_default_timezone_set('America/New_York');

$dryRun = in_array('--dry-run', $argv, true);

$reminders = [
    3 => [
        'title' => 'Pickup reminder',
        'body'  => 'Your approved borrow of "%s" is waiting for pickup. '
                 . 'Please arrange pickup soon — the approval expires after 7 days.',
    ],
    5 => [
        'title' => 'Pickup reminder — 2 days left',
        'body'  => 'Your approved borrow of "%s" has been waiting %d days for pickup. '
                 . 'Please pick up within 2 days or it will be auto-cancelled.',
    ],
    6 => [
        'title' => 'Pickup window closing tomorrow',
        'body'  => 'Your approved borrow of "%s" will be auto-cancelled tomorrow '
                 . 'if not picked up. This is your final reminder.',
    ],
];

$stale = Borrow::getStaleApproved(3);

if ($stale === []) {
    echo "[" . date('Y-m-d H:i:s') . "] No approved borrows past 3 days.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($stale) . " approved borrow(s) past 3 days.\n";

$sent   = 0;
$errors = 0;

foreach ($stale as $row) {
    $borrowId   = (int) $row['id_bor'];
    $borrowerId = (int) $row['borrower_id'];
    $toolName   = $row['tool_name_tol'];
    $days       = (int) $row['days_since_approved'];

    $tier = match (true) {
        $days >= 6 => 6,
        $days >= 5 => 5,
        default    => 3,
    };

    $title = $reminders[$tier]['title'];
    $body  = sprintf($reminders[$tier]['body'], $toolName, $days);

    if (Notification::existsForBorrow($borrowerId, $title, $borrowId)) {
        continue;
    }

    echo "  Borrow #{$borrowId} \"{$toolName}\" ({$days} days) — tier {$tier}";

    if ($dryRun) {
        echo " [DRY RUN]\n";
        continue;
    }

    try {
        Notification::send($borrowerId, 'approval', $title, $body, $borrowId);
        echo " — sent\n";
        $sent++;
    } catch (\Throwable $e) {
        echo " — ERROR: " . $e->getMessage() . "\n";
        error_log("remind-pickup: borrow #{$borrowId}: " . $e->getMessage());
        $errors++;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Done. Sent: {$sent}, Errors: {$errors}\n";

exit($errors > 0 ? 1 : 0);
