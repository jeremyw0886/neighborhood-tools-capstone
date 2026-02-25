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
}
