<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class EventAttendance
{
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
