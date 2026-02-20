<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Event
{
    private const int PER_PAGE = 12;

    private const array VALID_TIMINGS = [
        'HAPPENING NOW',
        'THIS WEEK',
        'THIS MONTH',
        'UPCOMING',
    ];

    /**
     * Fetch upcoming events with optional timing filter and pagination.
     *
     * Queries upcoming_event_v which computes days_until_event and
     * event_timing (HAPPENING NOW / THIS WEEK / THIS MONTH / UPCOMING).
     * Events are ordered by proximity: happening now first, then soonest.
     *
     * @param  ?string $timing  One of the VALID_TIMINGS constants, or null for all
     * @return array            Rows from upcoming_event_v
     */
    public static function getUpcoming(
        ?string $timing = null,
        int $limit = self::PER_PAGE,
        int $offset = 0,
    ): array {
        $pdo = Database::connection();

        $where = '';
        if ($timing !== null && in_array($timing, self::VALID_TIMINGS, true)) {
            $where = 'WHERE event_timing = :timing';
        }

        $sql = "
            SELECT *
            FROM upcoming_event_v
            {$where}
            ORDER BY
                FIELD(event_timing, 'HAPPENING NOW', 'THIS WEEK', 'THIS MONTH', 'UPCOMING'),
                start_at_evt ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);

        if ($where !== '') {
            $stmt->bindValue(':timing', $timing, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count upcoming events, optionally filtered by timing.
     */
    public static function getUpcomingCount(?string $timing = null): int
    {
        $pdo = Database::connection();

        $where = '';
        if ($timing !== null && in_array($timing, self::VALID_TIMINGS, true)) {
            $where = 'WHERE event_timing = :timing';
        }

        $sql = "SELECT COUNT(*) FROM upcoming_event_v {$where}";

        $stmt = $pdo->prepare($sql);

        if ($where !== '') {
            $stmt->bindValue(':timing', $timing, PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch timing facets with counts for the filter bar.
     *
     * Returns an associative array keyed by timing label with the
     * count of events in each bucket — used to display filter options
     * with their event counts.
     *
     * @return array<string, int>  e.g. ['HAPPENING NOW' => 2, 'THIS WEEK' => 5, ...]
     */
    public static function getTimingCounts(): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT event_timing, COUNT(*) AS event_count
            FROM upcoming_event_v
            GROUP BY event_timing
            ORDER BY FIELD(event_timing, 'HAPPENING NOW', 'THIS WEEK', 'THIS MONTH', 'UPCOMING')
        ";

        $rows = $pdo->query($sql)->fetchAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['event_timing']] = (int) $row['event_count'];
        }

        return $counts;
    }
}
