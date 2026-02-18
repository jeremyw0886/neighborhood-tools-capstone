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

    /**
     * Create a borrow request via sp_create_borrow_request().
     *
     * The SP validates tool availability internally via fn_is_tool_available()
     * and creates the availability block atomically. Uses MySQL user variables
     * for OUT parameters to avoid PDO driver inconsistencies.
     *
     * @param  int     $toolId             Tool being requested
     * @param  int     $borrowerId         Account placing the request
     * @param  int     $loanDurationHours  Requested loan period in hours
     * @param  ?string $notes              Optional notes from borrower
     * @return array{borrow_id: ?int, error: ?string}
     */
    public static function create(
        int $toolId,
        int $borrowerId,
        int $loanDurationHours,
        ?string $notes = null,
    ): array {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_create_borrow_request(:tool_id, :borrower_id, :duration, :notes, @borrow_id, @error_msg)'
        );

        $stmt->bindValue(':tool_id', $toolId, PDO::PARAM_INT);
        $stmt->bindValue(':borrower_id', $borrowerId, PDO::PARAM_INT);
        $stmt->bindValue(':duration', $loanDurationHours, PDO::PARAM_INT);
        $stmt->bindValue(':notes', $notes, $notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @borrow_id AS borrow_id, @error_msg AS error')->fetch();

        return [
            'borrow_id' => $out['borrow_id'] !== null ? (int) $out['borrow_id'] : null,
            'error'     => $out['error'],
        ];
    }

    /**
     * Find a single pending borrow request by ID.
     *
     * Queries pending_request_v (filtered to "requested" status) so the
     * controller can verify ownership and gather notification context
     * before calling approve() or deny().
     */
    public static function findPendingById(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM pending_request_v WHERE id_bor = :id');
        $stmt->bindValue(':id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Approve a borrow request via sp_approve_borrow_request().
     *
     * The SP validates ownership and status transitions internally.
     * Uses MySQL user variables for OUT parameters.
     *
     * @return array{success: bool, error: ?string}
     */
    public static function approve(int $borrowId, int $approverId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_approve_borrow_request(:borrow_id, :approver_id, @success, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':approver_id', $approverId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    /**
     * Fetch borrow history for a user via sp_get_user_borrow_history().
     *
     * Returns completed, denied, and cancelled borrows for the given role.
     *
     * @param  int     $accountId  Account to fetch history for
     * @param  string  $role       'lender' or 'borrower'
     * @param  ?string $status     Optional status filter (null = all terminal statuses)
     * @param  int     $limit      Max rows to return
     * @param  int     $offset     Pagination offset
     * @return array   Rows from the stored procedure result set
     */
    public static function getUserHistory(
        int $accountId,
        string $role,
        ?string $status = null,
        int $limit = 20,
        int $offset = 0,
    ): array {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('CALL sp_get_user_borrow_history(:id, :role, :status, :lim, :off)');
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, $status === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $stmt->closeCursor();

        return $rows;
    }
}
