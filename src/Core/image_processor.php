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
    private const int WEBP_QUALITY = 80;

    /**
     * Resize an image file in-place to a maximum width.
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

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => null,
        };

        if ($source === null) {
            return;
        }

        $canvas = imagecreatetruecolor($newW, $newH);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($canvas, $path, self::JPEG_QUALITY),
            IMAGETYPE_PNG  => imagepng($canvas, $path, self::PNG_COMPRESSION),
            IMAGETYPE_WEBP => imagewebp($canvas, $path, self::WEBP_QUALITY),
        };

        unset($source, $canvas);
    }

    /**
     * Create a WebP variant of an image file.
     *
     * @param non-empty-string $path Absolute path to the source image
     * @return ?string Path to the created WebP file, or null on failure
     */
    public static function createWebpVariant(string $path): ?string
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

        imagewebp($source, $webpPath, self::WEBP_QUALITY);
        unset($source);

        return $webpPath;
    }

    /**
     * Generate size variants and WebP copies for a source image.
     *
     * @param non-empty-string $sourcePath Absolute path to the full-size image
     * @param int[] $widths Variant widths to generate
     * @return string[] Paths of all created files (empty on failure)
     */
    public static function generateVariants(
        string $sourcePath,
        array $widths = [1200, 800, 400],
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

                self::resize($variantPath, $w);

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
        array $widths = [400, 800, 1200],
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

        foreach ($variants as $width => $v) {
            $srcsetParts[] = $urlPrefix . $v['file'] . ' ' . $width . 'w';
            if (isset($v['webp'])) {
                $webpParts[] = $urlPrefix . $v['webp'] . ' ' . $width . 'w';
            }
        }

        return [
            'srcset'     => implode(', ', $srcsetParts),
            'webpSrcset' => implode(', ', $webpParts),
        ];
    }

    /**
     * Delete all variant files for a given image filename.
     *
     * @param string $filename  Base filename (e.g., "tool_xxx.jpg")
     * @param string $uploadDir Subdirectory under public/uploads/
     */
    public static function deleteVariants(string $filename, string $uploadDir = 'tools'): void
    {
        $dir  = BASE_PATH . '/public/uploads/' . $uploadDir . '/';
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $isWebp = strtolower($ext) === 'webp';

        $variants = [
            $filename,
            "{$name}-1200w.{$ext}",
            "{$name}-800w.{$ext}",
            "{$name}-400w.{$ext}",
            "{$name}-220w.{$ext}",
        ];

        foreach ($variants as $variant) {
            $path = $dir . $variant;
            if (file_exists($path)) {
                unlink($path);
            }

            if (!$isWebp) {
                $webp = preg_replace('/\.\w+$/', '.webp', $path);
                if (file_exists($webp)) {
                    unlink($webp);
                }
            }
        }
    }
}
