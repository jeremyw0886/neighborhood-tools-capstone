<?php

/**
 * Generate optimized WebP variants for existing tool images.
 *
 * Resizes full-size images to 750w max (from 800w), then creates
 * WebP variants at quality 65 for both full and 400w card sizes.
 *
 * Run from project root: php scripts/convert-webp.php [--force]
 */

$uploadsDir = __DIR__ . '/../public/uploads/tools';
$maxWidth   = 750;
$quality    = 65;
$force      = in_array('--force', $argv ?? [], true);
$resized    = 0;
$converted  = 0;
$skipped    = 0;

foreach (glob($uploadsDir . '/*.{jpg,png}', GLOB_BRACE) as $path) {
    if (str_contains(basename($path), '-400w.')) {
        continue;
    }

    $info = getimagesize($path);
    if ($info === false) {
        echo "SKIP (unreadable): " . basename($path) . "\n";
        continue;
    }

    [$origW, $origH, $type] = $info;

    if ($origW > $maxWidth) {
        $newW = $maxWidth;
        $newH = (int) round($origH * ($maxWidth / $origW));

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            default        => null,
        };

        if ($source !== null) {
            $canvas = imagecreatetruecolor($newW, $newH);

            if ($type === IMAGETYPE_PNG) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $transparent);
            }

            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            match ($type) {
                IMAGETYPE_JPEG => imagejpeg($canvas, $path, 82),
                IMAGETYPE_PNG  => imagepng($canvas, $path, 6),
            };

            echo basename($path) . ": {$origW}w → {$newW}w\n";
            $resized++;
        }
    }

    $webpPath = preg_replace('/\.\w+$/', '.webp', $path);

    if (!$force && file_exists($webpPath)) {
        $skipped++;
    } else {
        $src = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            default        => null,
        };

        if ($src !== null) {
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($src, true);
                imagesavealpha($src, true);
            }

            imagewebp($src, $webpPath, $quality);
            $converted++;

            $origSize = filesize($path);
            $webpSize = filesize($webpPath);
            $savings  = round((1 - $webpSize / $origSize) * 100);

            echo basename($path) . " → " . basename($webpPath)
               . " ({$savings}% smaller)\n";
        }
    }
}

foreach (glob($uploadsDir . '/*-400w.{jpg,png}', GLOB_BRACE) as $path) {
    $webpPath = preg_replace('/\.\w+$/', '.webp', $path);

    if (!$force && file_exists($webpPath)) {
        $skipped++;
        continue;
    }

    $info = getimagesize($path);
    if ($info === false) {
        continue;
    }

    $src = match ($info[2]) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => imagecreatefrompng($path),
        default        => null,
    };

    if ($src === null) {
        continue;
    }

    if ($info[2] === IMAGETYPE_PNG) {
        imagealphablending($src, true);
        imagesavealpha($src, true);
    }

    imagewebp($src, $webpPath, $quality);
    $converted++;

    $origSize = filesize($path);
    $webpSize = filesize($webpPath);
    $savings  = round((1 - $webpSize / $origSize) * 100);

    echo basename($path) . " → " . basename($webpPath)
       . " ({$savings}% smaller)\n";
}

echo "\nDone. Resized: {$resized}, WebP created: {$converted}, Skipped: {$skipped}\n";
