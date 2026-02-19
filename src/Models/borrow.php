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
     * Count active borrows for a user in a specific role.
     *
     * @param  string $role 'borrower' or 'lender'
     */
    public static function getActiveCountForUser(int $accountId, string $role = 'borrower'): int
    {
        $column = $role === 'lender' ? 'lender_id' : 'borrower_id';
        $pdo    = Database::connection();

        $sql = "SELECT COUNT(*) FROM active_borrow_v WHERE {$column} = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count pending requests for a user in a specific role.
     *
     * @param  string $role 'borrower' or 'lender'
     */
    public static function getPendingCountForUser(int $accountId, string $role = 'lender'): int
    {
        $column = $role === 'lender' ? 'lender_id' : 'borrower_id';
        $pdo    = Database::connection();

        $sql = "SELECT COUNT(*) FROM pending_request_v WHERE {$column} = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count overdue borrows for a user in a specific role.
     *
     * @param  string $role 'borrower' or 'lender'
     */
    public static function getOverdueCountForUser(int $accountId, string $role = 'borrower'): int
    {
        $column = $role === 'lender' ? 'lender_id' : 'borrower_id';
        $pdo    = Database::connection();

        $sql = "SELECT COUNT(*) FROM overdue_borrow_v WHERE {$column} = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
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
     * Find a borrow record by ID regardless of status.
     *
     * Returns core fields needed for authorization checks and
     * notification context. Used by actions that span multiple
     * statuses (e.g. cancel works on "requested" + "approved").
     */
    public static function findById(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                b.id_bor,
                b.id_acc_bor        AS borrower_id,
                t.id_acc_tol        AS lender_id,
                t.tool_name_tol,
                bst.status_name_bst AS borrow_status,
                CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
                CONCAT(lender.first_name_acc, ' ', lender.last_name_acc)     AS lender_name
            FROM borrow_bor b
            JOIN tool_tol t            ON b.id_tol_bor = t.id_tol
            JOIN borrow_status_bst bst ON b.id_bst_bor  = bst.id_bst
            JOIN account_acc borrower  ON b.id_acc_bor   = borrower.id_acc
            JOIN account_acc lender    ON t.id_acc_tol   = lender.id_acc
            WHERE b.id_bor = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
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
     * Deny a borrow request via sp_deny_borrow_request().
     *
     * The SP validates ownership and status transitions internally,
     * and appends the denial reason to the borrow notes.
     *
     * @return array{success: bool, error: ?string}
     */
    public static function deny(int $borrowId, int $denierId, string $reason): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_deny_borrow_request(:borrow_id, :denier_id, :reason, @success, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':denier_id', $denierId, PDO::PARAM_INT);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    /**
     * Cancel a borrow request via sp_cancel_borrow_request().
     *
     * Either the borrower or the lender can cancel requests in
     * "requested" or "approved" status. The SP appends the reason to notes.
     *
     * @return array{success: bool, error: ?string}
     */
    public static function cancel(int $borrowId, int $cancellerId, string $reason): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_cancel_borrow_request(:borrow_id, :canceller_id, :reason, @success, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':canceller_id', $cancellerId, PDO::PARAM_INT);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    /**
     * Find a single active borrow by ID.
     *
     * Queries active_borrow_v (filtered to "borrowed" status) so the
     * controller can verify ownership before calling completeReturn().
     */
    public static function findActiveById(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM active_borrow_v WHERE id_bor = :id');
        $stmt->bindValue(':id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Complete a tool pickup via sp_complete_pickup().
     *
     * The SP validates the borrow is in "approved" status, transitions
     * it to "borrowed", sets borrowed_at, calculates due_at, and creates
     * an availability block for the tool.
     *
     * @return array{success: bool, error: ?string}
     */
    public static function completePickup(int $borrowId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_complete_pickup(:borrow_id, @success, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    /**
     * Complete a tool return via sp_complete_return().
     *
     * The SP validates the borrow is in "borrowed" status, transitions
     * it to "returned", sets returned_at, and removes the availability block.
     *
     * @return array{success: bool, error: ?string}
     */
    public static function completeReturn(int $borrowId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_complete_return(:borrow_id, @success, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    /**
     * Extend an active loan via sp_extend_loan().
     *
     * The SP adds extra hours to the due date, inserts an audit record
     * into loan_extension_lex, and updates borrow_bor.due_at_bor.
     * Errors are raised as exceptions (RESIGNAL), not OUT params.
     *
     * @param  int     $borrowId    Active borrow to extend
     * @param  int     $extraHours  Additional hours to grant (must be > 0)
     * @param  string  $reason      Why the extension was granted
     * @param  int     $approvedBy  Account ID of the lender approving
     */
    public static function extend(
        int $borrowId,
        int $extraHours,
        string $reason,
        int $approvedBy,
    ): void {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_extend_loan(:bor_id, :extra_hours, :reason, :approved_by)'
        );

        $stmt->bindValue(':bor_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':extra_hours', $extraHours, PDO::PARAM_INT);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        $stmt->bindValue(':approved_by', $approvedBy, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
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
