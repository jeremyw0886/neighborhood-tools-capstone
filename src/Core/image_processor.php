<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Facade for image processing — delegates to MagickBackend or GdBackend.
 */
final class ImageProcessor
{
    public const array VARIANT_WIDTHS = [1200, 900, 600, 400];

    private const float ASPECT_RATIO = 3 / 2;

    private const array FORMAT_VARIANTS = ['webp', 'avif'];

    private static ?ImageBackend $backend = null;

    private static function backend(): ImageBackend
    {
        return self::$backend ??= self::detectBackend();
    }

    private static function detectBackend(): ImageBackend
    {
        $disabled = array_map(trim(...), explode(',', ini_get('disable_functions') ?: ''));
        if (in_array('exec', $disabled, true)) {
            return new GdBackend();
        }

        $candidates = array_filter([
            $_ENV['MAGICK_BINARY'] ?? null,
            '/bin/magick',
            '/usr/bin/magick',
            '/usr/local/bin/magick',
            'magick',
        ]);

        foreach ($candidates as $path) {
            $output = [];
            $code = 0;
            @exec(escapeshellarg($path) . ' -version 2>/dev/null', $output, $code);
            if ($code === 0) {
                return new MagickBackend($path);
            }
        }

        return new GdBackend();
    }

    /**
     * Resize an image file in-place to a maximum width, preserving aspect ratio.
     *
     * @param non-empty-string $path
     */
    public static function resize(string $path, int $maxWidth): void
    {
        self::backend()->resize($path, $maxWidth);
    }

    /**
     * Crop to a target aspect ratio around a focal point, then resize.
     *
     * @param non-empty-string $path
     * @param ?float $aspectRatio Width/height ratio (null = 3:2, 1.0 = square)
     */
    public static function cropResize(
        string $path,
        int $targetWidth,
        int $focalX = 50,
        int $focalY = 50,
        ?float $aspectRatio = null,
    ): void {
        self::backend()->cropResize(
            $path,
            $targetWidth,
            $focalX,
            $focalY,
            $aspectRatio ?? self::ASPECT_RATIO,
        );
    }

    /**
     * Get the intrinsic width of an image file.
     *
     * @param non-empty-string $path
     */
    public static function getIntrinsicWidth(string $path): ?int
    {
        return self::backend()->getIntrinsicWidth($path);
    }

    /**
     * Rotate the file at $path per its EXIF Orientation tag, then clear the tag.
     *
     * @param non-empty-string $path
     */
    public static function autoOrient(string $path): void
    {
        self::backend()->autoOrient($path);
    }

    /**
     * Generate size variants and format copies for a source image.
     *
     * @param non-empty-string $sourcePath
     * @param int[]            $widths
     * @param ?float           $aspectRatio    Width/height ratio (null = 3:2, 1.0 = square)
     * @param ?string          $outputDir      Absolute directory for variant writes; null = source's directory
     * @param bool             $preserveSource Skip in-place re-encoding of the source (regen flow)
     * @return string[] Paths of all created files (empty on failure)
     */
    public static function generateVariants(
        string $sourcePath,
        array $widths = self::VARIANT_WIDTHS,
        int $focalX = 50,
        int $focalY = 50,
        ?float $aspectRatio = null,
        ?string $outputDir = null,
        bool $preserveSource = false,
    ): array {
        $size = getimagesize($sourcePath);
        if ($size === false) {
            return [];
        }

        $sourceWidth = $size[0];
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $isWebp = strtolower($ext) === 'webp';
        $base = self::resolveOutputBase($sourcePath, $outputDir);
        $ratio = $aspectRatio ?? self::ASPECT_RATIO;
        $backend = self::backend();

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

                $backend->cropResize($variantPath, $w, $focalX, $focalY, $ratio);

                if (!$isWebp) {
                    foreach (self::FORMAT_VARIANTS as $format) {
                        $quality = self::qualityForWidth($w, $format);
                        $formatPath = $backend->createFormatVariant($variantPath, $format, $quality);
                        if ($formatPath !== null) {
                            $created[] = $formatPath;
                        }
                    }
                }
            }

            if (!$preserveSource) {
                $backend->cropResize($sourcePath, $sourceWidth, $focalX, $focalY, $ratio);

                if (!$isWebp) {
                    foreach (self::FORMAT_VARIANTS as $format) {
                        $quality = self::qualityForWidth($sourceWidth, $format);
                        $formatPath = $backend->createFormatVariant($sourcePath, $format, $quality);
                        if ($formatPath !== null) {
                            $created[] = $formatPath;
                        }
                    }
                }
            }
        } catch (\RuntimeException) {
            self::cleanupFiles($created);
            return [];
        }

        return $created;
    }

    /**
     * Generate native-aspect size variants (no crop) for a source image.
     *
     * @param non-empty-string $sourcePath
     * @param int[]            $widths
     * @param ?string          $outputDir      Absolute directory for variant writes; null = source's directory
     * @param bool             $preserveSource Skip writing source-width format variants (regen flow)
     * @return string[] Paths of all created files (empty on failure)
     */
    public static function generateResizedVariants(
        string $sourcePath,
        array $widths,
        ?string $outputDir = null,
        bool $preserveSource = false,
    ): array {
        $size = getimagesize($sourcePath);
        if ($size === false) {
            return [];
        }

        $sourceWidth = $size[0];
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $isWebp = strtolower($ext) === 'webp';
        $base = self::resolveOutputBase($sourcePath, $outputDir);
        $backend = self::backend();

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

                $backend->resize($variantPath, $w);

                if (!$isWebp) {
                    foreach (self::FORMAT_VARIANTS as $format) {
                        $quality = self::qualityForWidth($w, $format);
                        $formatPath = $backend->createFormatVariant($variantPath, $format, $quality);
                        if ($formatPath !== null) {
                            $created[] = $formatPath;
                        }
                    }
                }
            }

            if (!$preserveSource && !$isWebp) {
                foreach (self::FORMAT_VARIANTS as $format) {
                    $quality = self::qualityForWidth($sourceWidth, $format);
                    $formatPath = $backend->createFormatVariant($sourcePath, $format, $quality);
                    if ($formatPath !== null) {
                        $created[] = $formatPath;
                    }
                }
            }
        } catch (\RuntimeException) {
            self::cleanupFiles($created);
            return [];
        }

        return $created;
    }

    /**
     * Resolve the variant-filename base path, honoring an optional output directory.
     */
    private static function resolveOutputBase(string $sourcePath, ?string $outputDir): string
    {
        if ($outputDir === null) {
            return preg_replace('/\.\w+$/', '', $sourcePath);
        }

        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        return rtrim($outputDir, '/') . '/' . $filename;
    }

    /**
     * Tiered quality — smaller variants get lower quality (imperceptible at that size).
     */
    private static function qualityForWidth(int $width, string $format): int
    {
        return match (true) {
            $format === 'avif' && $width >= 820 => 55,
            $format === 'avif'                  => 45,
            $format === 'webp' && $width >= 820 => 82,
            $format === 'webp'                  => 72,
            $width >= 820                       => 90,
            default                             => 82,
        };
    }

    /**
     * Delete an array of file paths.
     *
     * @param string[] $paths
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
     * Build srcset data from variants that exist on disk.
     *
     * @param string $filename  Base image filename (e.g., "tool_xxx.jpg")
     * @param ?int   $fullWidth Intrinsic width from DB (null = measure from file)
     * @param int[]  $widths    Variant widths to check
     * @param string $uploadDir Subdirectory under public/uploads/
     * @return array<int, array{file: string, webp?: string, avif?: string}> Keyed by width
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
                    $avifFile = "{$name}-{$w}w.avif";
                    if (file_exists($base . $avifFile)) {
                        $entry['avif'] = $avifFile;
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
                $avifFull = "{$name}.avif";
                if (file_exists($base . $avifFull)) {
                    $entry['avif'] = $avifFull;
                }
            }
            $variants[$fullWidth] = $entry;
        }

        return $variants;
    }

    /**
     * Build srcset strings from variant data.
     *
     * @param array<int, array{file: string, webp?: string, avif?: string}> $variants
     * @param string $urlPrefix URL path prefix (e.g., "/uploads/tools/")
     * @return array{srcset: string, webpSrcset: string, avifSrcset: string}
     */
    public static function buildSrcset(
        array $variants,
        string $urlPrefix = '/uploads/tools/',
    ): array {
        ksort($variants);

        $srcsetParts = [];
        $webpParts   = [];
        $avifParts   = [];

        $diskBase = BASE_PATH . '/public' . $urlPrefix;

        foreach ($variants as $width => $v) {
            $mtime = filemtime($diskBase . $v['file']) ?: 0;
            $ver = '?v=' . $mtime;
            $srcsetParts[] = $urlPrefix . $v['file'] . $ver . ' ' . $width . 'w';

            if (isset($v['webp'])) {
                $webpMtime = filemtime($diskBase . $v['webp']) ?: 0;
                $webpParts[] = $urlPrefix . $v['webp'] . '?v=' . $webpMtime . ' ' . $width . 'w';
            }

            if (isset($v['avif'])) {
                $avifMtime = filemtime($diskBase . $v['avif']) ?: 0;
                $avifParts[] = $urlPrefix . $v['avif'] . '?v=' . $avifMtime . ' ' . $width . 'w';
            }
        }

        return [
            'srcset'     => implode(', ', $srcsetParts),
            'webpSrcset' => implode(', ', $webpParts),
            'avifSrcset' => implode(', ', $avifParts),
        ];
    }

    /**
     * Delete sized variant files only (keeps the original).
     *
     * @param string $filename  Base filename (e.g., "tool_xxx.jpg")
     * @param string $uploadDir Subdirectory under public/uploads/
     * @param int[]  $widths    Variant widths to delete
     */
    public static function deleteVariantsOnly(
        string $filename,
        string $uploadDir = 'tools',
        array $widths = self::VARIANT_WIDTHS,
    ): void {
        $dir  = BASE_PATH . '/public/uploads/' . $uploadDir . '/';
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $isWebp = strtolower($ext) === 'webp';

        foreach ($widths as $w) {
            $path = $dir . "{$name}-{$w}w.{$ext}";
            if (file_exists($path)) {
                unlink($path);
            }

            if (!$isWebp) {
                foreach (self::FORMAT_VARIANTS as $format) {
                    $formatPath = $dir . "{$name}-{$w}w.{$format}";
                    if (file_exists($formatPath)) {
                        unlink($formatPath);
                    }
                }
            }
        }
    }

    /**
     * Delete all files for an image (original + variants).
     *
     * @param string $filename  Base filename (e.g., "tool_xxx.jpg")
     * @param string $uploadDir Subdirectory under public/uploads/
     * @param int[]  $widths    Variant widths to delete
     */
    public static function deleteVariants(
        string $filename,
        string $uploadDir = 'tools',
        array $widths = self::VARIANT_WIDTHS,
    ): void {
        self::deleteVariantsOnly($filename, $uploadDir, $widths);

        $dir  = BASE_PATH . '/public/uploads/' . $uploadDir . '/';
        $path = $dir . $filename;
        if (file_exists($path)) {
            unlink($path);
        }

        $isWebp = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'webp';
        if (!$isWebp) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            foreach (self::FORMAT_VARIANTS as $format) {
                $fullFormatPath = $dir . "{$name}.{$format}";
                if (file_exists($fullFormatPath)) {
                    unlink($fullFormatPath);
                }
            }
        }
    }
}
