<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Borrow
{
    /**
     * Fetch active (currently borrowed) items where the user is borrower OR lender.
     *
     * Queries active_borrow_v which only includes rows in "borrowed" status.
     *
     * @return array  Rows with tool name, due date, due status, counterparty info
     */
    public static function getActiveForUser(int $accountId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM active_borrow_v
            WHERE borrower_id = :id OR lender_id = :id2
            ORDER BY due_at_bor ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':id2', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch pending borrow requests where the user is borrower OR lender.
     *
     * Queries pending_request_v which only includes rows in "requested" status.
     *
     * @return array  Rows with tool name, requester info, hours pending
     */
    public static function getPendingForUser(int $accountId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM pending_request_v
            WHERE borrower_id = :id OR lender_id = :id2
            ORDER BY requested_at_bor DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':id2', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch overdue borrows where the user is borrower OR lender.
     *
     * Queries overdue_borrow_v which filters for past-due items.
     *
     * @return array  Rows with tool name, hours/days overdue, deposit info
     */
    public static function getOverdueForUser(int $accountId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM overdue_borrow_v
            WHERE borrower_id = :id OR lender_id = :id2
            ORDER BY hours_overdue DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':id2', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count active borrows for a user (as borrower or lender).
     */
    public static function getActiveCountForUser(int $accountId): int
    {
        $pdo = Database::connection();

        $sql = "
            SELECT COUNT(*)
            FROM active_borrow_v
            WHERE borrower_id = :id OR lender_id = :id2
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':id2', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count pending requests for a user (as borrower or lender).
     */
    public static function getPendingCountForUser(int $accountId): int
    {
        $pdo = Database::connection();

        $sql = "
            SELECT COUNT(*)
            FROM pending_request_v
            WHERE borrower_id = :id OR lender_id = :id2
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':id2', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count overdue borrows for a user (as borrower or lender).
     */
    public static function getOverdueCountForUser(int $accountId): int
    {
        $pdo = Database::connection();

        $sql = "
            SELECT COUNT(*)
            FROM overdue_borrow_v
            WHERE borrower_id = :id OR lender_id = :id2
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':id2', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
