<?php

declare(strict_types=1);

namespace App\Core;

/**
 * GD-based image processing backend.
 */
final class GdBackend implements ImageBackend
{
    private const int JPEG_QUALITY = 90;
    private const int PNG_COMPRESSION = 4;
    private const int WEBP_QUALITY = 85;

    #[\Override]
    public function resize(string $path, int $maxWidth): void
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

        $source = $this->loadImage($path, $type);
        if ($source === null) {
            return;
        }

        $canvas = $this->createCanvas($newW, $newH, $type);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        $this->saveImage($canvas, $path, $type);

        unset($source, $canvas);
    }

    #[\Override]
    public function cropResize(
        string $path,
        int $targetWidth,
        int $focalX,
        int $focalY,
        float $aspectRatio,
    ): void {
        $info = getimagesize($path);
        if ($info === false) {
            return;
        }

        [$origW, $origH, $type] = $info;

        $targetH = (int) round($targetWidth / $aspectRatio);
        $sourceRatio = $origW / $origH;

        if ($sourceRatio > $aspectRatio) {
            $cropH = $origH;
            $cropW = (int) round($origH * $aspectRatio);
        } else {
            $cropW = $origW;
            $cropH = (int) round($origW / $aspectRatio);
        }

        $cropX = (int) round(($origW - $cropW) * ($focalX / 100));
        $cropY = (int) round(($origH - $cropH) * ($focalY / 100));

        $cropX = max(0, min($cropX, $origW - $cropW));
        $cropY = max(0, min($cropY, $origH - $cropH));

        $source = $this->loadImage($path, $type);
        if ($source === null) {
            return;
        }

        $canvas = $this->createCanvas($targetWidth, $targetH, $type);
        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetH, $cropW, $cropH);
        $this->saveImage($canvas, $path, $type);

        unset($source, $canvas);
    }

    #[\Override]
    public function createFormatVariant(string $path, string $format, int $quality): ?string
    {
        if ($format === 'avif') {
            return null;
        }

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

        if (!preg_match('/\.\w+$/', $path)) {
            throw new \RuntimeException("GdBackend::createFormatVariant: path missing extension: {$path}");
        }

        $outputPath = preg_replace('/\.\w+$/', '.webp', $path);

        if ($info[2] === IMAGETYPE_PNG) {
            imagealphablending($source, true);
            imagesavealpha($source, true);
        }

        imagewebp($source, $outputPath, $quality);
        unset($source);

        return $outputPath;
    }

    #[\Override]
    public function autoOrient(string $path): void
    {
        $info = getimagesize($path);
        if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
            error_log("GdBackend::autoOrient skipped (non-JPEG or unreadable): {$path}");
            return;
        }

        $exif = @exif_read_data($path);
        if ($exif === false || !isset($exif['Orientation'])) {
            error_log("GdBackend::autoOrient skipped (no EXIF Orientation): {$path}");
            return;
        }

        $orientation = (int) $exif['Orientation'];
        if ($orientation === 1) {
            return;
        }

        try {
            $source = imagecreatefromjpeg($path);
            if ($source === false) {
                error_log("GdBackend::autoOrient failed to decode: {$path}");
                return;
            }

            $rotated = match ($orientation) {
                2 => self::flipHorizontal($source),
                3 => imagerotate($source, 180, 0),
                4 => self::flipVertical($source),
                5 => self::flipHorizontal(imagerotate($source, -90, 0)),
                6 => imagerotate($source, -90, 0),
                7 => self::flipHorizontal(imagerotate($source, 90, 0)),
                8 => imagerotate($source, 90, 0),
                default => $source,
            };

            imagejpeg($rotated, $path, self::JPEG_QUALITY);
            unset($source, $rotated);
        } catch (\Throwable $e) {
            error_log('GdBackend::autoOrient failed: ' . $path . ' — ' . $e->getMessage());
        }
    }

    #[\Override]
    public function getIntrinsicWidth(string $path): ?int
    {
        $size = getimagesize($path);
        return $size !== false ? $size[0] : null;
    }

    private static function flipHorizontal(\GdImage $image): \GdImage
    {
        imageflip($image, IMG_FLIP_HORIZONTAL);
        return $image;
    }

    private static function flipVertical(\GdImage $image): \GdImage
    {
        imageflip($image, IMG_FLIP_VERTICAL);
        return $image;
    }

    private function loadImage(string $path, int $type): ?\GdImage
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => null,
        };
    }

    private function createCanvas(int $width, int $height, int $type): \GdImage
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

    private function saveImage(\GdImage $image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, self::JPEG_QUALITY),
            IMAGETYPE_PNG  => imagepng($image, $path, self::PNG_COMPRESSION),
            IMAGETYPE_WEBP => imagewebp($image, $path, self::WEBP_QUALITY),
        };
    }
}
