<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Reusable GD image processing for uploads.
 */
final class ImageProcessor
{
    private const int JPEG_QUALITY = 82;
    private const int PNG_COMPRESSION = 6;
    private const int WEBP_QUALITY = 75;

    public const array VARIANT_WIDTHS = [820, 750, 540, 360];

    private const float ASPECT_RATIO = 3 / 2;

    /**
     * Resize an image file in-place to a maximum width, preserving aspect ratio.
     *
     * @param non-empty-string $path Absolute path to the image file
     */
    public static function resize(string $path, int $maxWidth): void
    {
        $info = getimagesize($path);
        if ($info === false) {
            return;
        }

        [$origW, $origH, $type] = $info;

        if ($origW <= $maxWidth) {
            return;
        }

        $newW = $maxWidth;
        $newH = (int) round($origH * ($maxWidth / $origW));

        $source = self::loadImage($path, $type);
        if ($source === null) {
            return;
        }

        $canvas = self::createCanvas($newW, $newH, $type);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        self::saveImage($canvas, $path, $type);

        unset($source, $canvas);
    }

    /**
     * Crop to 3:2 around a focal point, then resize to target width.
     *
     * @param non-empty-string $path Absolute path to the image file
     */
    public static function cropResize(string $path, int $targetWidth, int $focalX = 50, int $focalY = 50): void
    {
        $info = getimagesize($path);
        if ($info === false) {
            return;
        }

        [$origW, $origH, $type] = $info;

        $targetH = (int) round($targetWidth / self::ASPECT_RATIO);
        $sourceRatio = $origW / $origH;

        if ($sourceRatio > self::ASPECT_RATIO) {
            $cropH = $origH;
            $cropW = (int) round($origH * self::ASPECT_RATIO);
        } else {
            $cropW = $origW;
            $cropH = (int) round($origW / self::ASPECT_RATIO);
        }

        $cropX = (int) round(($origW - $cropW) * ($focalX / 100));
        $cropY = (int) round(($origH - $cropH) * ($focalY / 100));

        $cropX = max(0, min($cropX, $origW - $cropW));
        $cropY = max(0, min($cropY, $origH - $cropH));

        $source = self::loadImage($path, $type);
        if ($source === null) {
            return;
        }

        $canvas = self::createCanvas($targetWidth, $targetH, $type);
        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetH, $cropW, $cropH);
        self::saveImage($canvas, $path, $type);

        unset($source, $canvas);
    }

    /**
     * @return \GdImage|null
     */
    private static function loadImage(string $path, int $type): ?\GdImage
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => null,
        };
    }

    /**
     * @return \GdImage
     */
    private static function createCanvas(int $width, int $height, int $type): \GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        }

        return $canvas;
    }

    private static function saveImage(\GdImage $image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, self::JPEG_QUALITY),
            IMAGETYPE_PNG  => imagepng($image, $path, self::PNG_COMPRESSION),
            IMAGETYPE_WEBP => imagewebp($image, $path, self::WEBP_QUALITY),
        };
    }

    /**
     * Create a WebP variant of an image file.
     *
     * @param non-empty-string $path Absolute path to the source image
     * @return ?string Path to the created WebP file, or null on failure
     */
    public static function createWebpVariant(string $path, ?int $quality = null): ?string
    {
        $info = getimagesize($path);
        if ($info === false) {
            return null;
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            default        => null,
        };

        if ($source === null) {
            return null;
        }

        $webpPath = preg_replace('/\.\w+$/', '.webp', $path);

        if ($info[2] === IMAGETYPE_PNG) {
            imagealphablending($source, true);
            imagesavealpha($source, true);
        }

        imagewebp($source, $webpPath, $quality ?? self::WEBP_QUALITY);
        unset($source);

        return $webpPath;
    }

    /**
     * Generate size variants (cropped to 3:2) and WebP copies for a source image.
     *
     * @param non-empty-string $sourcePath Absolute path to the full-size image
     * @param int[] $widths Variant widths to generate
     * @return string[] Paths of all created files (empty on failure)
     */
    public static function generateVariants(
        string $sourcePath,
        array $widths = self::VARIANT_WIDTHS,
        int $focalX = 50,
        int $focalY = 50,
    ): array {
        $size = getimagesize($sourcePath);
        if ($size === false) {
            return [];
        }

        $sourceWidth = $size[0];
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $isWebp = strtolower($ext) === 'webp';
        $base = preg_replace('/\.\w+$/', '', $sourcePath);

        $qualifyingWidths = array_filter(
            $widths,
            static fn(int $w): bool => $w < $sourceWidth,
        );

        rsort($qualifyingWidths);

        $created = [];

        try {
            foreach ($qualifyingWidths as $w) {
                $variantPath = "{$base}-{$w}w.{$ext}";

                if (!copy($sourcePath, $variantPath)) {
                    throw new \RuntimeException("Failed to copy variant: {$variantPath}");
                }
                $created[] = $variantPath;

                self::cropResize($variantPath, $w, $focalX, $focalY);

                if (!$isWebp) {
                    $webpPath = self::createWebpVariant($variantPath);
                    if ($webpPath !== null) {
                        $created[] = $webpPath;
                    }
                }
            }

            if (!$isWebp) {
                $webpFull = self::createWebpVariant($sourcePath);
                if ($webpFull !== null) {
                    $created[] = $webpFull;
                }
            }
        } catch (\RuntimeException) {
            self::cleanupFiles($created);
            return [];
        }

        return $created;
    }

    /**
     * Delete an array of file paths.
     *
     * @param string[] $paths Absolute paths to remove
     */
    public static function cleanupFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Get the intrinsic width of an image file.
     *
     * @param non-empty-string $path Absolute path to the image
     */
    public static function getIntrinsicWidth(string $path): ?int
    {
        $size = getimagesize($path);
        return $size !== false ? $size[0] : null;
    }

    /**
     * Build srcset data from variants that exist on disk.
     *
     * @param string $filename  Base image filename (e.g., "tool_xxx.jpg")
     * @param ?int   $fullWidth Intrinsic width from DB (null = measure from file)
     * @param int[]  $widths    Variant widths to check
     * @param string $uploadDir Subdirectory under public/uploads/
     * @return array<int, array{file: string, webp?: string}> Keyed by actual width
     */
    public static function getAvailableVariants(
        string $filename,
        ?int $fullWidth = null,
        array $widths = self::VARIANT_WIDTHS,
        string $uploadDir = 'tools',
    ): array {
        $name  = pathinfo($filename, PATHINFO_FILENAME);
        $ext   = pathinfo($filename, PATHINFO_EXTENSION);
        $base  = BASE_PATH . '/public/uploads/' . $uploadDir . '/';
        $isWebp = strtolower($ext) === 'webp';

        $variants = [];

        foreach ($widths as $w) {
            $variantFile = "{$name}-{$w}w.{$ext}";
            if (file_exists($base . $variantFile)) {
                $entry = ['file' => $variantFile];
                if (!$isWebp) {
                    $webpFile = "{$name}-{$w}w.webp";
                    if (file_exists($base . $webpFile)) {
                        $entry['webp'] = $webpFile;
                    }
                }
                $variants[$w] = $entry;
            }
        }

        if ($fullWidth === null) {
            $fullPath = $base . $filename;
            if (file_exists($fullPath)) {
                $size = getimagesize($fullPath);
                $fullWidth = $size !== false ? $size[0] : null;
            }
        }

        if ($fullWidth !== null) {
            $entry = ['file' => $filename];
            if (!$isWebp) {
                $webpFull = "{$name}.webp";
                if (file_exists($base . $webpFull)) {
                    $entry['webp'] = $webpFull;
                }
            }
            $variants[$fullWidth] = $entry;
        }

        return $variants;
    }

    /**
     * Build srcset and WebP srcset strings from variant data.
     *
     * @param array<int, array{file: string, webp?: string}> $variants
     * @param string $urlPrefix URL path prefix (e.g., "/uploads/tools/")
     * @return array{srcset: string, webpSrcset: string}
     */
    public static function buildSrcset(
        array $variants,
        string $urlPrefix = '/uploads/tools/',
    ): array {
        ksort($variants);

        $srcsetParts = [];
        $webpParts   = [];

        $version = defined('ASSET_VERSION') ? '?v=' . ASSET_VERSION : '';

        foreach ($variants as $width => $v) {
            $srcsetParts[] = $urlPrefix . $v['file'] . $version . ' ' . $width . 'w';
            if (isset($v['webp'])) {
                $webpParts[] = $urlPrefix . $v['webp'] . $version . ' ' . $width . 'w';
            }
        }

        return [
            'srcset'     => implode(', ', $srcsetParts),
            'webpSrcset' => implode(', ', $webpParts),
        ];
    }

    /**
     * Delete sized variant files only (keeps the original).
     *
     * @param string $filename  Base filename (e.g., "tool_xxx.jpg")
     * @param string $uploadDir Subdirectory under public/uploads/
     */
    public static function deleteVariantsOnly(string $filename, string $uploadDir = 'tools'): void
    {
        $dir  = BASE_PATH . '/public/uploads/' . $uploadDir . '/';
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $isWebp = strtolower($ext) === 'webp';

        foreach (self::VARIANT_WIDTHS as $w) {
            $path = $dir . "{$name}-{$w}w.{$ext}";
            if (file_exists($path)) {
                unlink($path);
            }

            if (!$isWebp) {
                $webp = $dir . "{$name}-{$w}w.webp";
                if (file_exists($webp)) {
                    unlink($webp);
                }
            }
        }
    }

    /**
     * Delete all files for an image (original + variants).
     *
     * @param string $filename  Base filename (e.g., "tool_xxx.jpg")
     * @param string $uploadDir Subdirectory under public/uploads/
     */
    public static function deleteVariants(string $filename, string $uploadDir = 'tools'): void
    {
        self::deleteVariantsOnly($filename, $uploadDir);

        $dir  = BASE_PATH . '/public/uploads/' . $uploadDir . '/';
        $path = $dir . $filename;
        if (file_exists($path)) {
            unlink($path);
        }

        $isWebp = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'webp';
        if (!$isWebp) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $webp = $dir . $name . '.webp';
            if (file_exists($webp)) {
                unlink($webp);
            }
        }
    }
}
