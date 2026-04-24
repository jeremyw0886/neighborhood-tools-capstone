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
            $days = (int) ($hours / 24);
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
     * Check whether an avatar row stores a vector (SVG) avatar filename.
     *
     * @param  ?string $vectorFilename account_acc.id_avv_acc → avatar_vector_avv.file_name_avv
     * @return bool    TRUE when a non-empty vector filename is present
     */
    public static function isVectorAvatar(?string $vectorFilename): bool
    {
        return !empty($vectorFilename);
    }

    /**
     * Resolve an avatar to its best URL (vector > cropped variant > full > placeholder).
     *
     * @param  ?string $vectorAvatar Vector filename
     * @param  ?string $photo        Profile photo filename
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
                $mtime = filemtime($dir . $variant) ?: 0;
                return '/uploads/profiles/' . $variant . '?v=' . $mtime;
            }

            if (file_exists($dir . $photo)) {
                $mtime = filemtime($dir . $photo) ?: 0;
                return '/uploads/profiles/' . $photo . '?v=' . $mtime;
            }
        }

        return '/assets/images/avatar-placeholder.svg';
    }

    /**
     * Format a location as "neighborhood, city, state", dropping empty parts
     * and collapsing consecutive duplicates (e.g. Weaverville neighborhood in
     * Weaverville city renders as "Weaverville, NC", not "Weaverville, Weaverville, NC").
     */
    public static function formatLocation(
        ?string $neighborhood,
        ?string $city,
        ?string $state,
    ): string {
        $parts = [];
        foreach ([$neighborhood, $city, $state] as $part) {
            $part = $part !== null ? trim($part) : '';
            if ($part === '') continue;
            if (end($parts) !== false && strcasecmp(end($parts), $part) === 0) continue;
            $parts[] = $part;
        }

        return implode(', ', $parts);
    }

    /**
     * Append a filemtime cache-buster to an upload URL.
     *
     * @param  string $uploadPath Root-relative URL (e.g. "/uploads/tools/photo.jpg")
     * @return string URL with ?v={mtime} appended
     */
    public static function uploadVersion(string $uploadPath): string
    {
        $disk = BASE_PATH . '/public' . $uploadPath;
        $mtime = file_exists($disk) ? (filemtime($disk) ?: 0) : 0;

        return $uploadPath . '?v=' . $mtime;
    }

    /**
     * Render a TOS paragraph body: escape HTML, linkify emails/URLs, keep line breaks.
     *
     * @param  list<string> $lines Raw content lines (unescaped)
     * @return string Safe HTML fragment
     */
    public static function renderTosBody(array $lines): string
    {
        $text = trim(implode("\n", $lines));
        if ($text === '') {
            return '';
        }

        $html = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = (string) preg_replace(
            '/\b([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/',
            '<a href="mailto:$1">$1</a>',
            $html,
        );
        $html = (string) preg_replace_callback(
            '/(?<!["\/])(\b)(https?:\/\/[^\s<]+|neighborhoodtools\.org\/\S+)/',
            static fn(array $m): string => $m[1] . '<a href="'
                . (str_starts_with($m[2], 'http') ? $m[2] : 'https://' . $m[2])
                . '" rel="noopener">' . $m[2] . '</a>',
            $html,
        );

        return nl2br($html, false);
    }

    /**
     * Build a slug for a TOS section anchor (e.g. "tos-3-user-conduct").
     *
     * @return string Lowercase kebab-case slug prefixed with "tos-{number}-"
     */
    public static function tosSectionSlug(int $number, string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

        return 'tos-' . $number . '-' . trim($slug, '-');
    }
}
