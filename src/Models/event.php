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

    /**
     * Insert a new event into event_evt with optional key/value metadata.
     *
     * Combines separate date + time inputs into TIMESTAMP columns.
     * Meta pairs are inserted into event_meta_evm (one row per key).
     *
     * @param  array<string, string> $meta  Optional key/value pairs for event_meta_evm
     * @return int                          The new event's id_evt
     */
    public static function create(
        string $name,
        ?string $description,
        string $startAt,
        ?string $endAt,
        ?int $neighborhoodId,
        int $creatorId,
        array $meta = [],
    ): int {
        $pdo = Database::connection();

        $sql = "
            INSERT INTO event_evt (
                event_name_evt,
                event_description_evt,
                start_at_evt,
                end_at_evt,
                id_nbh_evt,
                id_acc_evt
            ) VALUES (
                :name,
                :description,
                :start_at,
                :end_at,
                :neighborhood_id,
                :creator_id
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':description', $description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':start_at', $startAt);
        $stmt->bindValue(':end_at', $endAt, $endAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':neighborhood_id', $neighborhoodId, $neighborhoodId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':creator_id', $creatorId, PDO::PARAM_INT);
        $stmt->execute();

        $eventId = (int) $pdo->lastInsertId();

        if ($meta !== []) {
            $metaSql = "
                INSERT INTO event_meta_evm (id_evt_evm, meta_key_evm, meta_value_evm)
                VALUES (:event_id, :meta_key, :meta_value)
            ";

            $metaStmt = $pdo->prepare($metaSql);

            foreach ($meta as $key => $value) {
                $metaStmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
                $metaStmt->bindValue(':meta_key', $key);
                $metaStmt->bindValue(':meta_value', $value);
                $metaStmt->execute();
            }
        }

        return $eventId;
    }
}
