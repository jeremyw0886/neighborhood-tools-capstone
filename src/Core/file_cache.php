<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Shared file-based TTL cache for expensive query results.
 *
 * Mirrors the storage pattern of RateLimiter but holds arbitrary
 * JSON-serialisable payloads. Shared across all sessions/users —
 * use this (not $_SESSION) when first-visit cold latency matters
 * (SEO, Lighthouse, bots, one-shot visitors).
 *
 * Safe under concurrent writes (LOCK_EX). Reads that lose the race
 * with a concurrent cleanup return a miss and recompute; acceptable
 * since the cost is the same as an initial miss.
 */
class FileCache
{
    private const string STORAGE_DIR = '/storage/cache/';
    private const int CLEANUP_PROBABILITY = 100;

    /**
     * Return the cached value for `$key` or, on miss, call `$producer`
     * and cache its return for `$ttl` seconds before returning it.
     *
     * @template T
     * @param  string        $key       Cache key
     * @param  int           $ttl       Seconds until the entry expires
     * @param  callable(): T $producer  Callback that computes the value on a miss
     * @return T
     */
    public static function remember(string $key, int $ttl, callable $producer): mixed
    {
        $path  = self::filePath($key);
        $state = self::readState($path);

        if ($state !== null) {
            return $state['value'];
        }

        $value = $producer();

        self::writeState($path, [
            'value'      => $value,
            'expires_at' => time() + $ttl,
        ]);

        if (random_int(1, self::CLEANUP_PROBABILITY) === 1) {
            self::cleanup();
        }

        return $value;
    }

    /**
     * Invalidate the cached value for `$key` if one exists.
     *
     * Safe to call when no entry exists (the unlink is suppressed).
     */
    public static function forget(string $key): void
    {
        @unlink(self::filePath($key));
    }

    private static function storagePath(): string
    {
        $dir = BASE_PATH . self::STORAGE_DIR;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private static function filePath(string $key): string
    {
        return self::storagePath() . hash('sha256', $key) . '.json';
    }

    private static function readState(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = @file_get_contents($path);

        if ($content === false || $content === '' || !json_validate($content)) {
            @unlink($path);
            return null;
        }

        $data = json_decode($content, associative: true);

        if (!is_array($data) || !isset($data['expires_at']) || !array_key_exists('value', $data)) {
            @unlink($path);
            return null;
        }

        if (time() >= (int) $data['expires_at']) {
            @unlink($path);
            return null;
        }

        return $data;
    }

    private static function writeState(string $path, array $state): void
    {
        file_put_contents(
            $path,
            json_encode($state, JSON_THROW_ON_ERROR),
            LOCK_EX,
        );
    }

    private static function cleanup(): void
    {
        $dir   = self::storagePath();
        $files = glob($dir . '*.json');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            self::readState($file);
        }
    }
}
