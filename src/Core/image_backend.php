<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contract for image processing backends (GD, ImageMagick CLI, etc.).
 */
interface ImageBackend
{
    /**
     * Resize an image in-place to a maximum width, preserving aspect ratio.
     *
     * @param non-empty-string $path
     */
    public function resize(string $path, int $maxWidth): void;

    /**
     * Crop to a target aspect ratio around a focal point, then resize.
     *
     * @param non-empty-string $path
     */
    public function cropResize(
        string $path,
        int $targetWidth,
        int $focalX,
        int $focalY,
        float $aspectRatio,
    ): void;

    /**
     * Create a format variant (WebP, AVIF) of an image file.
     *
     * @param non-empty-string $path
     * @param 'webp'|'avif' $format
     * @return ?string Path to the created file, or null on failure/unsupported
     */
    public function createFormatVariant(string $path, string $format, int $quality): ?string;

    /**
     * Get the intrinsic width of an image file.
     *
     * @param non-empty-string $path
     */
    public function getIntrinsicWidth(string $path): ?int;

    /**
     * Rotate the file at $path per its EXIF Orientation tag, then clear the tag.
     *
     * @param non-empty-string $path
     */
    public function autoOrient(string $path): void;
}
