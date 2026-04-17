<?php

declare(strict_types=1);

namespace App\Core;

/**
 * ImageMagick CLI backend using the `magick` binary.
 */
final class MagickBackend implements ImageBackend
{
    public function __construct(
        private readonly string $binary,
    ) {}

    #[\Override]
    public function resize(string $path, int $maxWidth): void
    {
        $info = getimagesize($path);
        if ($info === false || $info[0] <= $maxWidth) {
            return;
        }

        $this->exec([
            $this->binary,
            $path,
            '-filter', 'Lanczos',
            '-resize', "{$maxWidth}x",
            '-strip',
            $path,
        ]);
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

        [$origW, $origH] = $info;

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

        $geometry = "{$cropW}x{$cropH}+{$cropX}+{$cropY}";

        $this->exec([
            $this->binary,
            $path,
            '-strip',
            '-crop', $geometry,
            '+repage',
            '-filter', 'Lanczos',
            '-resize', "{$targetWidth}x{$targetH}!",
            $path,
        ]);
    }

    #[\Override]
    public function createFormatVariant(string $path, string $format, int $quality): ?string
    {
        $outputPath = preg_replace('/\.\w+$/', ".{$format}", $path);

        $success = $this->exec([
            $this->binary,
            $path,
            '-strip',
            '-quality', (string) $quality,
            $outputPath,
        ]);

        if (!$success || !file_exists($outputPath)) {
            return null;
        }

        return $outputPath;
    }

    #[\Override]
    public function getIntrinsicWidth(string $path): ?int
    {
        $output = [];
        $code = 0;
        $cmd = escapeshellarg($this->binary)
            . ' identify -ping -format %w '
            . escapeshellarg($path)
            . ' 2>/dev/null';

        @exec($cmd, $output, $code);

        if ($code === 0 && isset($output[0]) && ctype_digit($output[0])) {
            return (int) $output[0];
        }

        $size = getimagesize($path);
        return $size !== false ? $size[0] : null;
    }

    /**
     * Build and execute a magick command from an array of arguments.
     *
     * @param string[] $args
     */
    private function exec(array $args): bool
    {
        $cmd = implode(' ', array_map(escapeshellarg(...), $args));
        $cmd .= ' 2>&1';

        $output = [];
        $code = 0;
        @exec($cmd, $output, $code);

        if ($code !== 0) {
            error_log('MagickBackend: command failed (code ' . $code . '): ' . implode("\n", $output));
            return false;
        }

        return true;
    }
}
