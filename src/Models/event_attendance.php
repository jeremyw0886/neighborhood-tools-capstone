<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * RSVP membership for community events.
 *
 * Thin wrapper over `event_attendee_eya` — toggle in/out, attendance
 * lookups, and per-event headcounts (single + batch).
 */
class EventAttendance
{
    /**
     * Toggle the user's RSVP for an event.
     *
     * Tries DELETE first; if it removed a row the user was attending and
     * is now not. Otherwise INSERTs the attendance row. Treats the unique
     * constraint violation (SQLSTATE 23000) as success so a concurrent
     * double-click can't crash the request.
     *
     * @return bool  TRUE if the user is now attending, FALSE if they just left
     */
    public static function toggle(int $userId, int $eventId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            DELETE FROM event_attendee_eya
            WHERE id_acc_eya = :user AND id_evt_eya = :event
        ");

        $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':event', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO event_attendee_eya (id_acc_eya, id_evt_eya)
                VALUES (:user, :event)
            ");

            $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':event', $eventId, PDO::PARAM_INT);
            $stmt->execute();

            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return true;
            }
            throw $e;
        }
    }

    /**
     * Whether `$userId` currently has an RSVP for `$eventId`.
     */
    public static function isAttending(int $userId, int $eventId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT 1
            FROM event_attendee_eya
            WHERE id_acc_eya = :user AND id_evt_eya = :event
            LIMIT 1
        ");

        $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':event', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return int[]
     */
    public static function getEventIdsForUser(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT id_evt_eya
            FROM event_attendee_eya
            WHERE id_acc_eya = :userId
        ");

        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Headcount of RSVPs for a single event.
     */
    public static function getAttendeeCount(int $eventId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM event_attendee_eya
            WHERE id_evt_eya = :eventId
        ");

        $stmt->bindValue(':eventId', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param  int[] $eventIds
     * @return array<int, int>
     */
    public static function getAttendeeCounts(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $pdo = Database::connection();

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));

        $stmt = $pdo->prepare("
            SELECT id_evt_eya, COUNT(*) AS attendee_count
            FROM event_attendee_eya
            WHERE id_evt_eya IN ({$placeholders})
            GROUP BY id_evt_eya
        ");

        foreach (array_values($eventIds) as $i => $eid) {
            $stmt->bindValue($i + 1, (int) $eid, PDO::PARAM_INT);
        }

        $stmt->execute();

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['id_evt_eya']] = (int) $row['attendee_count'];
        }

        return $counts;
    }
}
