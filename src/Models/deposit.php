<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Deposit
{
    private const int PER_PAGE = 12;

    // Must match AdminController::DEPOSITS_ALLOWED_STATUSES
    private const array ALLOWED_STATUSES = ['pending', 'held', 'released', 'forfeited', 'partial_release'];

    // Must match AdminController::DEPOSITS_ALLOWED_ACTIONS
    private const array ALLOWED_ACTIONS = [
        'READY FOR RELEASE', 'OVERDUE - REVIEW NEEDED', 'ACTIVE BORROW',
        'PAYMENT PENDING', 'RELEASED', 'FORFEITED', 'PARTIAL RELEASE', 'REVIEW NEEDED',
    ];

    /**
     * Create a pending security deposit for a borrow.
     *
     * @param  string $provider  Payment provider name ('stripe', 'paypal', 'manual')
     * @return int    The new deposit ID
     */
    public static function create(int $borrowId, string $amount, string $provider = 'stripe'): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            INSERT INTO security_deposit_sdp (id_bor_sdp, id_dps_sdp, amount_sdp, id_ppv_sdp)
            VALUES (:borrow_id, fn_get_deposit_status_id(:status), :amount, (
                SELECT id_ppv FROM payment_provider_ppv WHERE provider_name_ppv = :provider
            ))
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':provider', $provider, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /**
     * Fetch paginated pending deposits for the admin reports page.
     *
     * Queries pending_deposit_v which joins security_deposit_sdp, borrow_bor,
     * tool_tol, and account_acc to show deposit holder, tool, and borrow context.
     *
     * @return array  Rows from pending_deposit_v
     */
    public static function getPending(int $limit = self::PER_PAGE, int $offset = 0): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM pending_deposit_v
            ORDER BY id_sdp DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count pending deposits platform-wide.
     */
    public static function getPendingCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query('SELECT COUNT(*) FROM pending_deposit_v')->fetchColumn();
    }

    /**
     * Find the deposit for a borrow regardless of status.
     */
    public static function findByBorrowId(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            SELECT sdp.id_sdp, sdp.id_bor_sdp, sdp.amount_sdp,
                   sdp.external_payment_id_sdp,
                   dps.status_name_dps AS deposit_status,
                   ppv.provider_name_ppv AS payment_provider
            FROM security_deposit_sdp sdp
            JOIN deposit_status_dps dps ON dps.id_dps = sdp.id_dps_sdp
            JOIN payment_provider_ppv ppv ON ppv.id_ppv = sdp.id_ppv_sdp
            WHERE sdp.id_bor_sdp = :borrow_id
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Find deposits for multiple borrows in a single query.
     *
     * @param  array<int> $ids  Borrow IDs
     * @return array<int, array>  Deposit rows keyed by borrow ID
     */
    public static function findByBorrowIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $pdo = Database::connection();

        $safe = implode(',', array_map('intval', $ids));

        $stmt = $pdo->query("
            SELECT sdp.id_sdp, sdp.id_bor_sdp, sdp.amount_sdp,
                   dps.status_name_dps AS deposit_status
            FROM security_deposit_sdp sdp
            JOIN deposit_status_dps dps ON dps.id_dps = sdp.id_dps_sdp
            WHERE sdp.id_bor_sdp IN ({$safe})
        ");

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['id_bor_sdp']] = $row;
        }

        return $result;
    }

    /**
     * Find a held deposit for a borrow, including payment provider and external ID.
     */
    public static function findHeldByBorrowId(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            SELECT sdp.id_sdp, sdp.id_bor_sdp, sdp.amount_sdp,
                   sdp.external_payment_id_sdp,
                   dps.status_name_dps AS deposit_status,
                   ppv.provider_name_ppv AS payment_provider
            FROM security_deposit_sdp sdp
            JOIN deposit_status_dps dps ON dps.id_dps = sdp.id_dps_sdp
            JOIN payment_provider_ppv ppv ON ppv.id_ppv = sdp.id_ppv_sdp
            WHERE sdp.id_bor_sdp = :borrow_id
              AND dps.status_name_dps = :status
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'held', PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Find a held deposit by ID via pending_deposit_v (held status only).
     *
     * @see findDetailById() for all-status lookup with full context
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM pending_deposit_v WHERE id_sdp = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Fetch full deposit detail by ID, regardless of status.
     *
     * @return ?array  Deposit row with borrow, tool, and account context
     */
    public static function findDetailById(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT sdp.id_sdp,
                   sdp.amount_sdp,
                   dps.status_name_dps AS deposit_status,
                   ppv.provider_name_ppv AS payment_provider,
                   sdp.external_payment_id_sdp,
                   sdp.held_at_sdp,
                   " . self::daysHeldCase() . " AS days_held,
                   sdp.released_at_sdp,
                   sdp.forfeited_at_sdp,
                   sdp.forfeited_amount_sdp,
                   sdp.forfeiture_reason_sdp,
                   sdp.id_bor_sdp,
                   bst.status_name_bst AS borrow_status,
                   b.due_at_bor,
                   " . self::actionRequiredCase() . " AS action_required,
                   t.id_tol,
                   t.tool_name_tol,
                   t.estimated_value_tol,
                   b.id_acc_bor AS borrower_id,
                   CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
                   borrower.email_address_acc AS borrower_email,
                   t.id_acc_tol AS lender_id,
                   CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
                   lender.email_address_acc AS lender_email,
                   COALESCE(incident_stats.incident_count, 0) AS incident_count,
                   sdp.id_irt_sdp AS linked_incident_id
            FROM security_deposit_sdp sdp
            JOIN deposit_status_dps dps ON sdp.id_dps_sdp = dps.id_dps
            JOIN payment_provider_ppv ppv ON sdp.id_ppv_sdp = ppv.id_ppv
            JOIN borrow_bor b ON sdp.id_bor_sdp = b.id_bor
            JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
            JOIN tool_tol t ON b.id_tol_bor = t.id_tol
            JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
            JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
            LEFT JOIN (
                SELECT id_bor_irt, COUNT(*) AS incident_count
                FROM incident_report_irt
                GROUP BY id_bor_irt
            ) incident_stats ON sdp.id_bor_sdp = incident_stats.id_bor_irt
            WHERE sdp.id_sdp = :id
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public static function findByIdRaw(int $id): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id_sdp, id_bor_sdp, id_dps_sdp,
                    dps.status_name_dps AS deposit_status
             FROM security_deposit_sdp
             JOIN deposit_status_dps dps ON dps.id_dps = id_dps_sdp
             WHERE id_sdp = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public static function release(int $borrowId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_release_deposit_on_return(:borrow_id, @success, @error_msg)'
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

    public static function forfeit(int $depositId, string $amount, string $reason, ?int $incidentId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_forfeit_deposit(:deposit_id, :amount, :reason, :incident_id, @success, @error_msg)'
        );

        $stmt->bindValue(':deposit_id',  $depositId, PDO::PARAM_INT);
        $stmt->bindValue(':amount',      $amount, PDO::PARAM_STR);
        $stmt->bindValue(':reason',      $reason, PDO::PARAM_STR);
        $stmt->bindValue(':incident_id', $incidentId, $incidentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }

    public static function getProviderIdByName(string $name): ?int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare('SELECT id_ppv FROM payment_provider_ppv WHERE provider_name_ppv = :name');
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? (int) $row['id_ppv'] : null;
    }

    /**
     * @return array{success: bool, id: ?int, error: ?string}
     */
    public static function createTransaction(
        ?int    $depositId,
        int     $borrowId,
        int     $providerId,
        string  $type,
        string  $amount,
        string  $externalId,
        ?string $externalStatus,
        ?int    $fromAccountId,
        ?int    $toAccountId,
    ): array {
        try {
            $pdo = Database::connection();

            $stmt = $pdo->prepare(
                'INSERT INTO payment_transaction_ptx
                    (id_sdp_ptx, id_bor_ptx, id_ppv_ptx, transaction_type_ptx,
                     amount_ptx, external_transaction_id_ptx, external_status_ptx,
                     id_acc_from_ptx, id_acc_to_ptx)
                 VALUES
                    (:deposit_id, :borrow_id, :provider_id, :type,
                     :amount, :external_id, :external_status,
                     :from_id, :to_id)'
            );

            $stmt->bindValue(':deposit_id',      $depositId, $depositId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':borrow_id',        $borrowId, PDO::PARAM_INT);
            $stmt->bindValue(':provider_id',      $providerId, PDO::PARAM_INT);
            $stmt->bindValue(':type',             $type, PDO::PARAM_STR);
            $stmt->bindValue(':amount',           $amount, PDO::PARAM_STR);
            $stmt->bindValue(':external_id',      $externalId, PDO::PARAM_STR);
            $stmt->bindValue(':external_status',  $externalStatus, $externalStatus === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':from_id',          $fromAccountId, $fromAccountId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':to_id',            $toAccountId, $toAccountId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->execute();

            return [
                'success' => true,
                'id'      => (int) $pdo->lastInsertId(),
                'error'   => null,
            ];
        } catch (\PDOException $e) {
            error_log('Deposit::createTransaction — ' . $e->getMessage());

            return [
                'success' => false,
                'id'      => null,
                'error'   => 'Failed to record transaction.',
            ];
        }
    }

    public static function createTransactionMeta(int $transactionId, string $key, string $value): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO payment_transaction_meta_ptm (id_ptx_ptm, meta_key_ptm, meta_value_ptm)
             VALUES (:tx_id, :key, :value)'
        );
        $stmt->bindValue(':tx_id', $transactionId, PDO::PARAM_INT);
        $stmt->bindValue(':key',   $key, PDO::PARAM_STR);
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function findPendingPayment(int $depositId): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT sdp.id_sdp, sdp.id_bor_sdp, sdp.amount_sdp,
                    sdp.external_payment_id_sdp,
                    bor.id_acc_bor        AS borrower_id,
                    tol.id_acc_tol        AS lender_id,
                    tol.tool_name_tol,
                    ppv.provider_name_ppv AS payment_provider
             FROM security_deposit_sdp sdp
             /* All FKs are NOT NULL per schema — INNER JOIN is safe */
             JOIN deposit_status_dps dps ON dps.id_dps = sdp.id_dps_sdp
             JOIN borrow_bor bor        ON bor.id_bor = sdp.id_bor_sdp
             JOIN tool_tol tol          ON tol.id_tol = bor.id_tol_bor
             JOIN payment_provider_ppv ppv ON ppv.id_ppv = sdp.id_ppv_sdp
             WHERE sdp.id_sdp = :id
               AND dps.status_name_dps = :status'
        );
        $stmt->bindValue(':id', $depositId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public static function transitionToHeld(int $depositId, string $externalPaymentId): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE security_deposit_sdp
             SET id_dps_sdp = fn_get_deposit_status_id(:status),
                 external_payment_id_sdp = :external_id,
                 held_at_sdp = NOW()
             WHERE id_sdp = :id
               AND id_dps_sdp = fn_get_deposit_status_id(:pending_status)'
        );
        $stmt->bindValue(':status', 'held', PDO::PARAM_STR);
        $stmt->bindValue(':external_id', $externalPaymentId, PDO::PARAM_STR);
        $stmt->bindValue(':id', $depositId, PDO::PARAM_INT);
        $stmt->bindValue(':pending_status', 'pending', PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("Deposit {$depositId} not in pending status or does not exist.");
        }
    }

    public static function storeExternalPaymentId(int $depositId, string $externalId): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE security_deposit_sdp
             SET external_payment_id_sdp = :external_id
             WHERE id_sdp = :id'
        );
        $stmt->bindValue(':external_id', $externalId, PDO::PARAM_STR);
        $stmt->bindValue(':id', $depositId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function getHistory(int $accountId, bool $isAdmin, int $limit, int $offset): array
    {
        $pdo = Database::connection();

        $sql = 'SELECT * FROM payment_history_v';

        if (!$isAdmin) {
            $sql .= ' WHERE id_acc_from_ptx = :acct_from OR id_acc_to_ptx = :acct_to';
        }

        $sql .= ' ORDER BY processed_at_ptx DESC LIMIT :lim OFFSET :off';

        $stmt = $pdo->prepare($sql);

        if (!$isAdmin) {
            $stmt->bindValue(':acct_from', $accountId, PDO::PARAM_INT);
            $stmt->bindValue(':acct_to',   $accountId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getHistoryCount(int $accountId, bool $isAdmin): int
    {
        $pdo = Database::connection();

        $sql = 'SELECT COUNT(*) FROM payment_history_v';

        if (!$isAdmin) {
            $sql .= ' WHERE id_acc_from_ptx = :acct_from OR id_acc_to_ptx = :acct_to';
        }

        $stmt = $pdo->prepare($sql);

        if (!$isAdmin) {
            $stmt->bindValue(':acct_from', $accountId, PDO::PARAM_INT);
            $stmt->bindValue(':acct_to',   $accountId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Return the CASE expression for the action_required column.
     *
     * The held-status branches use correlated subqueries for borrow status
     * lookups. For single-row queries (findDetailById) the cost is negligible.
     * For paginated lists (getAdminList) and especially when the action filter
     * is active (CASE evaluates twice per row in SELECT and WHERE), the cost
     * scales with row count — acceptable for expected admin deposit volume.
     *
     * @return string  Raw SQL CASE expression (no trailing comma or alias)
     */
    private static function actionRequiredCase(): string
    {
        return "CASE
                    WHEN dps.status_name_dps = 'released' THEN 'RELEASED'
                    WHEN dps.status_name_dps = 'forfeited' THEN 'FORFEITED'
                    WHEN dps.status_name_dps = 'partial_release' THEN 'PARTIAL RELEASE'
                    WHEN dps.status_name_dps = 'pending' THEN 'PAYMENT PENDING'
                    WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst
                                         WHERE status_name_bst = 'returned')
                         THEN 'READY FOR RELEASE'
                    WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst
                                         WHERE status_name_bst = 'borrowed')
                         AND b.due_at_bor < NOW()
                         THEN 'OVERDUE - REVIEW NEEDED'
                    WHEN b.id_bst_bor = (SELECT id_bst FROM borrow_status_bst
                                         WHERE status_name_bst = 'borrowed')
                         THEN 'ACTIVE BORROW'
                    ELSE 'REVIEW NEEDED'
                END";
    }

    /**
     * Return the CASE expression for the days_held column.
     *
     * Pending deposits return NULL (never held). Released deposits freeze at
     * held_at → released_at. Forfeited/partial_release freeze at held_at →
     * forfeited_at. Held deposits show a live count from held_at → NOW().
     *
     * @return string  Raw SQL CASE expression (no trailing comma or alias)
     */
    private static function daysHeldCase(): string
    {
        return "CASE WHEN dps.status_name_dps = 'pending' THEN NULL
                    WHEN sdp.held_at_sdp IS NULL THEN NULL
                    ELSE TIMESTAMPDIFF(DAY, sdp.held_at_sdp,
                         COALESCE(sdp.released_at_sdp, sdp.forfeited_at_sdp, NOW()))
                END";
    }

    /**
     * Return the shared FROM/JOIN clause for admin deposit queries.
     *
     * @return string  Raw SQL FROM clause with all joins
     */
    private static function adminBaseFrom(): string
    {
        return "FROM security_deposit_sdp sdp
                JOIN deposit_status_dps dps ON sdp.id_dps_sdp = dps.id_dps
                JOIN payment_provider_ppv ppv ON sdp.id_ppv_sdp = ppv.id_ppv
                JOIN borrow_bor b ON sdp.id_bor_sdp = b.id_bor
                JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
                JOIN tool_tol t ON b.id_tol_bor = t.id_tol
                JOIN account_acc borrower ON b.id_acc_bor = borrower.id_acc
                JOIN account_acc lender ON t.id_acc_tol = lender.id_acc
                LEFT JOIN (
                    SELECT id_bor_irt, COUNT(*) AS incident_count
                    FROM incident_report_irt
                    GROUP BY id_bor_irt
                ) incident_stats ON sdp.id_bor_sdp = incident_stats.id_bor_irt";
    }

    /**
     * Build WHERE clause and params for admin deposit filtering.
     *
     * @return array{where: string, params: array<string, mixed>}
     */
    private static function buildAdminWhere(
        ?string $status,
        ?string $action,
        ?string $search,
        bool $incidentsOnly = false,
    ): array {
        $conditions = [];
        $params     = [];

        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $conditions[] = 'dps.status_name_dps = :status';
            $params[':status'] = $status;
        }

        // MySQL cannot filter by column alias in WHERE, so the full CASE
        // expression is repeated here. This means the CASE evaluates twice
        // per row (SELECT + WHERE) when the action filter is active —
        // acceptable for expected admin deposit volume.
        if ($action !== null && in_array($action, self::ALLOWED_ACTIONS, true)) {
            $conditions[] = self::actionRequiredCase() . ' = :action_required';
            $params[':action_required'] = $action;
        }

        if ($search !== null) {
            $conditions[] = '(t.tool_name_tol LIKE :search1'
                . " OR CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) LIKE :search2"
                . " OR CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) LIKE :search3)";
            $term = '%' . $search . '%';
            $params[':search1'] = $term;
            $params[':search2'] = $term;
            $params[':search3'] = $term;
        }

        if ($incidentsOnly) {
            $conditions[] = 'incident_stats.incident_count > 0';
        }

        $where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return ['where' => $where, 'params' => $params];
    }

    /**
     * Fetch a paginated, filtered, sorted list of deposits for the admin view.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAdminList(
        int $limit,
        int $offset,
        string $sort = 'created_at_sdp',
        string $dir = 'DESC',
        ?string $status = null,
        ?string $action = null,
        ?string $search = null,
        bool $incidentsOnly = false,
    ): array {
        $validSortFields = [
            'amount_sdp', 'deposit_status', 'created_at_sdp',
            'days_held', 'tool_name_tol', 'borrower_name', 'lender_name',
        ];

        $sortCol = in_array($sort, $validSortFields, true) ? $sort : 'created_at_sdp';
        $dir     = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        $orderExpr = match ($sortCol) {
            'amount_sdp'     => 'sdp.amount_sdp',
            'deposit_status' => 'dps.status_name_dps',
            'created_at_sdp' => 'sdp.created_at_sdp',
            'days_held'      => 'days_held',
            'tool_name_tol'  => 't.tool_name_tol',
            'borrower_name'  => "CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc)",
            'lender_name'    => "CONCAT(lender.first_name_acc, ' ', lender.last_name_acc)",
        };

        $filter = self::buildAdminWhere($status, $action, $search, $incidentsOnly);

        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT sdp.id_sdp,
                   sdp.amount_sdp,
                   dps.status_name_dps AS deposit_status,
                   ppv.provider_name_ppv AS payment_provider,
                   sdp.held_at_sdp,
                   sdp.released_at_sdp,
                   sdp.forfeited_at_sdp,
                   sdp.forfeited_amount_sdp,
                   sdp.created_at_sdp,
                   " . self::daysHeldCase() . " AS days_held,
                   " . self::actionRequiredCase() . " AS action_required,
                   t.id_tol,
                   t.tool_name_tol,
                   sdp.id_bor_sdp,
                   bst.status_name_bst AS borrow_status,
                   borrower.id_acc AS borrower_id,
                   CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
                   lender.id_acc AS lender_id,
                   CONCAT(lender.first_name_acc, ' ', lender.last_name_acc) AS lender_name,
                   COALESCE(incident_stats.incident_count, 0) AS incident_count
            " . self::adminBaseFrom() . "
            {$filter['where']}
            ORDER BY {$orderExpr} {$dir}
            LIMIT :limit OFFSET :offset
        ");

        foreach ($filter['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count deposits matching the given admin filters.
     */
    public static function getAdminFilteredCount(
        ?string $status = null,
        ?string $action = null,
        ?string $search = null,
        bool $incidentsOnly = false,
    ): int {
        $filter = self::buildAdminWhere($status, $action, $search, $incidentsOnly);

        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            " . self::adminBaseFrom() . "
            {$filter['where']}
        ");

        foreach ($filter['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
