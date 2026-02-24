<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Borrow
{
    /**
     * Validate a sort field and direction against an allowlist.
     *
     * @param  string[] $allowed  Permitted column names
     * @return array{string, string}  Validated [field, direction]
     */
    private static function validateSort(
        string $field,
        string $dir,
        array $allowed,
        string $defaultField,
        string $defaultDir,
    ): array {
        $field = in_array($field, $allowed, true) ? $field : $defaultField;
        $dir   = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        return [$field, $dir === $defaultDir ? $defaultDir : $dir];
    }

    /**
     * Fetch active (currently borrowed) items where the user is borrower OR lender.
     *
     * @param  string $sort Allowed: due_at_bor, tool_name_tol, borrower_name, lender_name, hours_until_due
     * @param  string $dir  ASC or DESC
     * @return array  Rows with tool name, due date, due status, counterparty info
     */
    public static function getActiveForUser(
        int $accountId,
        string $sort = 'due_at_bor',
        string $dir = 'ASC',
    ): array {
        [$sort, $dir] = self::validateSort(
            $sort,
            $dir,
            ['due_at_bor', 'tool_name_tol', 'borrower_name', 'lender_name', 'hours_until_due'],
            'due_at_bor',
            'ASC',
        );

        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM active_borrow_v
            WHERE borrower_id = :id OR lender_id = :id2
            ORDER BY {$sort} {$dir}
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
    public static function getPendingForUser(
        int $accountId,
        string $sort = 'requested_at_bor',
        string $dir = 'DESC',
    ): array {
        [$sort, $dir] = self::validateSort(
            $sort,
            $dir,
            ['requested_at_bor', 'tool_name_tol', 'borrower_name', 'lender_name', 'hours_pending', 'loan_duration_hours_bor'],
            'requested_at_bor',
            'DESC',
        );

        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM pending_request_v
            WHERE borrower_id = :id OR lender_id = :id2
            ORDER BY {$sort} {$dir}
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
    public static function getOverdueForUser(
        int $accountId,
        string $sort = 'hours_overdue',
        string $dir = 'DESC',
    ): array {
        [$sort, $dir] = self::validateSort(
            $sort,
            $dir,
            ['hours_overdue', 'due_at_bor', 'tool_name_tol', 'borrower_name', 'lender_name'],
            'hours_overdue',
            'DESC',
        );

        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM overdue_borrow_v
            WHERE borrower_id = :id OR lender_id = :id2
            ORDER BY {$sort} {$dir}
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
     * Fetch approved borrows awaiting pickup where the user is borrower or lender.
     *
     * @return array Rows with tool name, counterparty info, approval timestamp
     */
    public static function getApprovedForUser(int $accountId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                b.id_bor,
                t.tool_name_tol,
                b.id_tol_bor,
                b.id_acc_bor                                                 AS borrower_id,
                CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
                t.id_acc_tol                                                 AS lender_id,
                CONCAT(lender.first_name_acc, ' ', lender.last_name_acc)     AS lender_name,
                b.approved_at_bor,
                b.loan_duration_hours_bor,
                b.notes_text_bor
            FROM borrow_bor b
            JOIN tool_tol t           ON b.id_tol_bor = t.id_tol
            JOIN account_acc borrower ON b.id_acc_bor  = borrower.id_acc
            JOIN account_acc lender   ON t.id_acc_tol  = lender.id_acc
            WHERE b.id_bst_bor = fn_get_borrow_status_id('approved')
              AND (b.id_acc_bor = :id OR t.id_acc_tol = :id2)
            ORDER BY b.approved_at_bor ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':id2', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count approved borrows awaiting pickup for a user in a specific role.
     *
     * @param string $role 'borrower' or 'lender'
     */
    public static function getApprovedCountForUser(int $accountId, string $role = 'borrower'): int
    {
        $column = $role === 'lender' ? 't.id_acc_tol' : 'b.id_acc_bor';
        $pdo    = Database::connection();

        $sql = "
            SELECT COUNT(*)
            FROM borrow_bor b
            JOIN tool_tol t ON b.id_tol_bor = t.id_tol
            WHERE b.id_bst_bor = fn_get_borrow_status_id('approved')
              AND {$column} = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch all non-terminal borrows for a user in a single unified query.
     *
     * Combines requested, approved, and borrowed statuses with urgency-based
     * ordering: overdue > due soon > on time > approved > requested.
     *
     * @param string $role 'lender' or 'borrower' — filters and sets counterparty
     * @return array Rows with status, counterparty info, due status, timestamps
     */
    public static function getAllActiveLoansForUser(int $accountId, string $role): array
    {
        $isLender         = $role === 'lender';
        $filterColumn     = $isLender ? 't.id_acc_tol' : 'b.id_acc_bor';
        $counterpartyId   = $isLender ? 'b.id_acc_bor' : 't.id_acc_tol';
        $counterpartyName = $isLender
            ? "CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc)"
            : "CONCAT(lender.first_name_acc, ' ', lender.last_name_acc)";

        $pdo = Database::connection();

        $sql = "
            SELECT
                b.id_bor,
                t.tool_name_tol,
                b.id_tol_bor,
                {$counterpartyId}   AS counterparty_id,
                {$counterpartyName} AS counterparty_name,
                bst.status_name_bst AS status_name,
                b.requested_at_bor,
                b.approved_at_bor,
                b.borrowed_at_bor,
                b.due_at_bor,
                TIMESTAMPDIFF(HOUR, NOW(), b.due_at_bor) AS hours_until_due,
                CASE
                    WHEN bst.status_name_bst != 'borrowed' THEN NULL
                    WHEN b.due_at_bor < NOW() THEN 'OVERDUE'
                    WHEN TIMESTAMPDIFF(HOUR, NOW(), b.due_at_bor) <= 24 THEN 'DUE SOON'
                    ELSE 'ON TIME'
                END AS due_status,
                b.loan_duration_hours_bor
            FROM borrow_bor b
            JOIN tool_tol t            ON b.id_tol_bor = t.id_tol
            JOIN borrow_status_bst bst ON b.id_bst_bor  = bst.id_bst
            JOIN account_acc borrower  ON b.id_acc_bor   = borrower.id_acc
            JOIN account_acc lender    ON t.id_acc_tol   = lender.id_acc
            WHERE bst.status_name_bst IN ('requested', 'approved', 'borrowed')
              AND {$filterColumn} = :id
            ORDER BY
                CASE
                    WHEN bst.status_name_bst = 'borrowed' AND b.due_at_bor < NOW() THEN 1
                    WHEN bst.status_name_bst = 'borrowed'
                         AND TIMESTAMPDIFF(HOUR, NOW(), b.due_at_bor) <= 24 THEN 2
                    WHEN bst.status_name_bst = 'borrowed' THEN 3
                    WHEN bst.status_name_bst = 'approved' THEN 4
                    ELSE 5
                END ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
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
                b.id_tol_bor,
                b.id_acc_bor                                                 AS borrower_id,
                t.id_acc_tol                                                 AS lender_id,
                t.tool_name_tol,
                bst.status_name_bst                                          AS borrow_status,
                CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
                CONCAT(lender.first_name_acc, ' ', lender.last_name_acc)     AS lender_name,
                b.requested_at_bor,
                b.approved_at_bor,
                b.borrowed_at_bor,
                b.due_at_bor,
                b.returned_at_bor,
                b.loan_duration_hours_bor,
                b.notes_text_bor,
                t.rental_fee_tol
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
        string $sort = 'requested_at_bor',
        string $dir = 'DESC',
    ): array {
        [$sort, $dir] = self::validateSort(
            $sort,
            $dir,
            ['requested_at_bor', 'tool_name_tol', 'borrower_name', 'lender_name', 'borrow_status'],
            'requested_at_bor',
            'DESC',
        );

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

        usort($rows, static function (array $a, array $b) use ($sort, $dir): int {
            $cmp = ($a[$sort] ?? '') <=> ($b[$sort] ?? '');
            return $dir === 'DESC' ? -$cmp : $cmp;
        });

        return $rows;
    }

    /**
     * Fetch loan extension history for a borrow.
     *
     * @return array Rows with extension hours, due dates, approver name
     */
    public static function getExtensions(int $borrowId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                lex.extended_hours_lex,
                lex.original_due_at_lex,
                lex.new_due_at_lex,
                lex.reason_lex,
                lex.created_at_lex,
                CONCAT(acc.first_name_acc, ' ', acc.last_name_acc) AS approved_by_name
            FROM loan_extension_lex lex
            JOIN account_acc acc ON lex.id_acc_approved_by_lex = acc.id_acc
            WHERE lex.id_bor_lex = :id
            ORDER BY lex.created_at_lex ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch handover verification records for a borrow.
     *
     * @return array Rows with handover type, verification timestamps, participant names
     */
    public static function getHandovers(int $borrowId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                hot.type_name_hot AS handover_type,
                hov.condition_notes_hov,
                hov.generated_at_hov,
                hov.verified_at_hov,
                CONCAT(gen.first_name_acc, ' ', gen.last_name_acc) AS generator_name,
                CONCAT(ver.first_name_acc, ' ', ver.last_name_acc) AS verifier_name
            FROM handover_verification_hov hov
            JOIN handover_type_hot hot ON hov.id_hot_hov = hot.id_hot
            JOIN account_acc gen       ON hov.id_acc_generator_hov = gen.id_acc
            LEFT JOIN account_acc ver  ON hov.id_acc_verifier_hov = ver.id_acc
            WHERE hov.id_bor_hov = :id
            ORDER BY hov.generated_at_hov ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
