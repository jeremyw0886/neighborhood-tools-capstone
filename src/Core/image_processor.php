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

        imagedestroy($source);
        imagedestroy($canvas);
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
        imagedestroy($source);

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
}
