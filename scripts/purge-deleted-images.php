<?php

declare(strict_types=1);

use App\Core\ImageProcessor;
use App\Models\Tool;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

date_default_timezone_set('America/New_York');

$dryRun        = in_array('--dry-run', $argv, true);
$retentionDays = 90;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--days=')) {
        $retentionDays = max(1, (int) substr($arg, 7));
    }
}

$timestamp = date('Y-m-d H:i:s');
$stale     = Tool::getStaleDeleted($retentionDays);

if ($stale === []) {
    echo "[{$timestamp}] No tools deleted {$retentionDays}+ days ago with images remaining.\n";
    exit(0);
}

echo "[{$timestamp}] Found " . count($stale) . " tool(s) deleted {$retentionDays}+ days ago.\n";

$totalFiles = 0;

foreach ($stale as $tool) {
    $toolId   = (int) $tool['id_tol'];
    $toolName = $tool['tool_name_tol'];

    if ($dryRun) {
        $images = Tool::getImages($toolId);
        echo "  [DRY RUN] Tool #{$toolId} \"{$toolName}\" — " . count($images) . " image(s) would be purged\n";
        continue;
    }

    try {
        $filenames = Tool::deleteAllImages($toolId);

        foreach ($filenames as $filename) {
            ImageProcessor::deleteVariants($filename);
        }

        $totalFiles += count($filenames);
        echo "  Purged tool #{$toolId} \"{$toolName}\" — " . count($filenames) . " image(s) removed\n";
    } catch (\Throwable $e) {
        error_log("purge-deleted-images: tool #{$toolId} — " . $e->getMessage());
        echo "  ERROR: tool #{$toolId} \"{$toolName}\" — {$e->getMessage()}\n";
    }
}

if (!$dryRun) {
    echo "[{$timestamp}] Done. Purged {$totalFiles} image(s) across " . count($stale) . " tool(s).\n";
}
