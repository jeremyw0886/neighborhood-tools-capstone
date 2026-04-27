<?php

declare(strict_types=1);

namespace App\Core;

use enshrined\svgSanitize\Sanitizer;

/**
 * Thin wrapper around enshrined/svg-sanitize so call sites depend on a
 * project-owned class rather than the vendored namespace, and so the
 * default policy (strip remote refs, minify, etc.) lives in one place.
 */
class SvgSanitizer
{
    /**
     * Sanitize an SVG file in place. Reads the file, runs the sanitizer,
     * and overwrites the file with the cleaned output.
     *
     * Returns true on success, false if the input could not be parsed
     * as SVG or could not be safely sanitized.
     */
    public static function sanitizeFile(string $path): bool
    {
        $raw = @file_get_contents($path);

        if ($raw === false || $raw === '') {
            return false;
        }

        $clean = self::sanitize($raw);

        if ($clean === null) {
            return false;
        }

        return @file_put_contents($path, $clean) !== false;
    }

    /**
     * Sanitize SVG markup and return the cleaned string, or null on failure.
     */
    public static function sanitize(string $raw): ?string
    {
        if (!class_exists(Sanitizer::class)) {
            error_log('SvgSanitizer: enshrined/svg-sanitize is not installed; rejecting upload. Run `composer install`.');
            return null;
        }

        $sanitizer = new Sanitizer();

        // Strip <use href="https://…"/> and friends — never load remote SVGs.
        $sanitizer->removeRemoteReferences(true);

        // Minify on the way out so the on-disk file matches the served bytes.
        $sanitizer->minify(true);

        $clean = $sanitizer->sanitize($raw);

        if ($clean === false || $clean === '') {
            return null;
        }

        return $clean;
    }
}
