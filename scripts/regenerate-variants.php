<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\ImageProcessor;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

date_default_timezone_set('America/New_York');

$dryRun = in_array('--dry-run', $argv, true);
$toolsOnly = in_array('--tools-only', $argv, true);
$profilesOnly = in_array('--profiles-only', $argv, true);

$oldToolWidths = [820, 750, 540, 360];
$newToolWidths = ImageProcessor::VARIANT_WIDTHS;
$profileWidths = [360, 150, 80];

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Variant regeneration " . ($dryRun ? '(DRY RUN) ' : '') . "started.\n";

$pdo = Database::connection();

$toolCount = 0;
$profileCount = 0;
$errorCount = 0;

if (!$profilesOnly) {
    $stmt = $pdo->query("
        SELECT id_tim, id_tol_tim, file_name_tim, focal_x_tim, focal_y_tim, width_tim
        FROM tool_image_tim
        ORDER BY id_tim ASC
    ");
    $toolImages = $stmt->fetchAll();

    echo "  Found " . count($toolImages) . " tool image(s).\n";

    foreach ($toolImages as $img) {
        $filename = $img['file_name_tim'];
        $focalX   = (int) ($img['focal_x_tim'] ?? 50);
        $focalY   = (int) ($img['focal_y_tim'] ?? 50);
        $sourcePath = BASE_PATH . '/public/uploads/tools/' . $filename;

        if (!file_exists($sourcePath)) {
            echo "  SKIP: {$filename} — source file missing\n";
            continue;
        }

        if ($dryRun) {
            echo "  [DRY RUN] Would regenerate: {$filename} (focal: {$focalX},{$focalY})\n";
            $toolCount++;
            continue;
        }

        try {
            ImageProcessor::deleteVariantsOnly($filename, widths: $oldToolWidths);

            ImageProcessor::generateVariants(
                $sourcePath,
                focalX: $focalX,
                focalY: $focalY,
            );

            $newWidth = ImageProcessor::getIntrinsicWidth($sourcePath);
            if ($newWidth !== null && $newWidth !== (int) ($img['width_tim'] ?? 0)) {
                $update = $pdo->prepare("
                    UPDATE tool_image_tim SET width_tim = :w WHERE id_tim = :id
                ");
                $update->bindValue(':w', $newWidth, PDO::PARAM_INT);
                $update->bindValue(':id', $img['id_tim'], PDO::PARAM_INT);
                $update->execute();
            }

            $toolCount++;
            echo "  OK: {$filename}\n";
        } catch (\Throwable $e) {
            $errorCount++;
            error_log("regenerate-variants: {$filename} — " . $e->getMessage());
            echo "  ERROR: {$filename} — {$e->getMessage()}\n";
        }
    }
}

if (!$toolsOnly) {
    $stmt = $pdo->query("
        SELECT id_aim, id_acc_aim, file_name_aim, focal_x_aim, focal_y_aim
        FROM account_image_aim
        ORDER BY id_aim ASC
    ");
    $profileImages = $stmt->fetchAll();

    echo "  Found " . count($profileImages) . " profile image(s).\n";

    foreach ($profileImages as $img) {
        $filename = $img['file_name_aim'];
        $focalX   = (int) ($img['focal_x_aim'] ?? 50);
        $focalY   = (int) ($img['focal_y_aim'] ?? 50);
        $sourcePath = BASE_PATH . '/public/uploads/profiles/' . $filename;

        if (!file_exists($sourcePath)) {
            echo "  SKIP: {$filename} — source file missing\n";
            continue;
        }

        if ($dryRun) {
            echo "  [DRY RUN] Would regenerate: {$filename} (focal: {$focalX},{$focalY})\n";
            $profileCount++;
            continue;
        }

        try {
            ImageProcessor::deleteVariantsOnly($filename, uploadDir: 'profiles', widths: $profileWidths);

            ImageProcessor::generateVariants(
                $sourcePath,
                widths: $profileWidths,
                focalX: $focalX,
                focalY: $focalY,
                aspectRatio: 1.0,
            );

            $profileCount++;
            echo "  OK: {$filename}\n";
        } catch (\Throwable $e) {
            $errorCount++;
            error_log("regenerate-variants: {$filename} — " . $e->getMessage());
            echo "  ERROR: {$filename} — {$e->getMessage()}\n";
        }
    }
}

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Done. Tools: {$toolCount}, Profiles: {$profileCount}, Errors: {$errorCount}\n";

if ($dryRun) {
    echo "  No files were modified. Run without --dry-run to apply changes.\n";
}
