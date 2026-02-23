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
}
