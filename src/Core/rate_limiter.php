<?php

declare(strict_types=1);

namespace App\Core;

class RateLimiter
{
    private const string STORAGE_DIR = '/storage/rate-limits/';
    private const int CLEANUP_MAX_WINDOW = 3600;

    public static function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $path  = self::filePath($key);
        $state = self::readState($path);

        if ($state === null) {
            return false;
        }

        $elapsed = time() - $state['first_attempt_at'];

        if ($elapsed >= $windowSeconds) {
            @unlink($path);
            return false;
        }

        if (random_int(1, 10) === 1) {
            self::cleanup();
        }

        return $state['attempts'] >= $maxAttempts;
    }

    public static function increment(string $key): void
    {
        $path  = self::filePath($key);
        $state = self::readState($path);
        $now   = time();

        if ($state === null || ($now - $state['first_attempt_at']) >= self::CLEANUP_MAX_WINDOW) {
            $state = ['attempts' => 0, 'first_attempt_at' => $now];
        }

        $state['attempts']++;
        self::writeState($path, $state);
    }

    public static function reset(string $key): void
    {
        $path = self::filePath($key);

        if (file_exists($path)) {
            @unlink($path);
        }
    }

    public static function remainingSeconds(string $key, int $windowSeconds): int
    {
        $path  = self::filePath($key);
        $state = self::readState($path);

        if ($state === null) {
            return 0;
        }

        $remaining = $windowSeconds - (time() - $state['first_attempt_at']);

        return max(0, $remaining);
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

        if ($content === false || $content === '') {
            return null;
        }

        if (!json_validate($content)) {
            @unlink($path);
            return null;
        }

        $data = json_decode($content, associative: true);

        if (!isset($data['attempts'], $data['first_attempt_at'])) {
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

        $now = time();

        foreach ($files as $file) {
            $state = self::readState($file);

            if ($state === null || ($now - $state['first_attempt_at']) >= self::CLEANUP_MAX_WINDOW) {
                @unlink($file);
            }
        }
    }
}
