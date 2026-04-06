<?php

declare(strict_types=1);

namespace App\Core;

class ViewHelper
{
    /**
     * Return ' selected' when two strings match, empty string otherwise.
     *
     * @return string HTML attribute fragment
     */
    public static function selected(string $actual, string $expected): string
    {
        return $actual === $expected ? ' selected' : '';
    }

    /**
     * Return an aria-sort attribute when the active sort field is in the list.
     *
     * @param  string   $sort   Active sort column
     * @param  string   $dir    Active sort direction (ASC|DESC)
     * @param  string   ...$fields Column names this <th> represents
     * @return string HTML attribute fragment
     */
    public static function ariaSort(string $sort, string $dir, string ...$fields): string
    {
        return in_array($sort, $fields, true)
            ? ' aria-sort="' . ($dir === 'ASC' ? 'ascending' : 'descending') . '"'
            : '';
    }

    /**
     * Format a loan duration in hours as a human-readable string.
     *
     * @param  int $hours Duration in hours
     * @return string Formatted duration (e.g. "2 days", "12 hours")
     */
    public static function formatDuration(int $hours): string
    {
        if ($hours >= 24 && $hours % 24 === 0) {
            $days = $hours / 24;
            return $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        if ($hours >= 24) {
            error_log("Unexpected non-24-multiple duration: {$hours} hours");
        }

        return $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
    }

    /**
     * Build a pagination URL preserving current filter/sort params.
     *
     * @param  string $basePath     URL path (e.g. '/admin/users')
     * @param  int    $pageNum      Target page number
     * @param  array  $filterParams Current filter state (nulls are stripped)
     * @param  string $pageParam    Query-string key for the page number
     * @return string Clean URL with query string
     */
    public static function adminPaginationUrl(
        string $basePath,
        int    $pageNum,
        array  $filterParams,
        string $pageParam = 'page',
    ): string {
        $params = array_filter($filterParams, static fn(mixed $v): bool => $v !== null && $v !== '');

        if ($pageNum > 1) {
            $params[$pageParam] = $pageNum;
        }

        $query = http_build_query($params);

        return $query !== '' ? $basePath . '?' . $query : $basePath;
    }

    /**
     * Resolve an avatar to its best URL (vector > cropped variant > full > placeholder).
     *
     * @param  ?string $vectorAvatar Vector filename (e.g. "fox.svg")
     * @param  ?string $photo        Profile photo filename (e.g. "profile_abc.jpg")
     * @param  int     $variantWidth Variant suffix width (default 80)
     * @return string  Root-relative URL ready for src attribute
     */
    public static function avatarUrl(
        ?string $vectorAvatar,
        ?string $photo,
        int     $variantWidth = 80,
    ): string {
        if ($vectorAvatar !== null && $vectorAvatar !== '') {
            return '/uploads/vectors/' . $vectorAvatar;
        }

        if ($photo !== null && $photo !== '') {
            $dir     = BASE_PATH . '/public/uploads/profiles/';
            $name    = pathinfo($photo, PATHINFO_FILENAME);
            $ext     = pathinfo($photo, PATHINFO_EXTENSION);
            $variant = $name . '-' . $variantWidth . 'w.' . $ext;

            if (file_exists($dir . $variant)) {
                return '/uploads/profiles/' . $variant;
            }

            if (file_exists($dir . $photo)) {
                return '/uploads/profiles/' . $photo;
            }
        }

        return '/assets/images/avatar-placeholder.svg';
    }
}
