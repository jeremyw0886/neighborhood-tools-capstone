<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Waiver
{
    /**
     * Fetch a pending (unsigned) waiver for a given borrow transaction.
     *
     * Queries pending_waiver_v which filters for approved borrows that
     * have no signed waiver yet. Returns tool context, lender info,
     * pre-existing conditions, and deposit requirements.
     *
     * @return array|null  Row from pending_waiver_v, or null if not found
     */
    public static function findPendingByBorrowId(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                id_bor,
                id_tol_bor,
                tool_name_tol,
                borrower_id,
                borrower_name,
                borrower_email,
                lender_id,
                lender_name,
                approved_at_bor,
                hours_since_approval,
                preexisting_conditions_tol,
                is_deposit_required_tol,
                default_deposit_amount_tol
            FROM pending_waiver_v
            WHERE id_bor = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Fetch all waiver type options from the lookup table.
     *
     * @return array  Rows with id_wtp and type_name_wtp
     */
    public static function getTypes(): array
    {
        $pdo = Database::connection();

        return $pdo->query('SELECT id_wtp, type_name_wtp FROM waiver_type_wtp ORDER BY id_wtp')
                    ->fetchAll();
    }

    /**
     * Record a signed waiver for a borrow transaction.
     *
     * Inserts a single row into borrow_waiver_bwv with all three
     * acknowledgment booleans set to TRUE. The DB trigger
     * trg_borrow_waiver_before_insert enforces that all three must
     * be TRUE; if not the insert is rejected via SIGNAL.
     *
     * @param  string|null $preexistingConditions  Snapshot of tool conditions at signing time
     */
    public static function sign(
        int $borrowId,
        int $accountId,
        ?string $preexistingConditions,
        string $ipAddress,
        string $userAgent,
    ): void {
        $pdo = Database::connection();

        $waiverTypeId = (int) $pdo->query(
            "SELECT id_wtp FROM waiver_type_wtp WHERE type_name_wtp = 'borrow_waiver'"
        )->fetchColumn();

        $sql = "
            INSERT INTO borrow_waiver_bwv (
                id_bor_bwv,
                id_wtp_bwv,
                id_acc_bwv,
                is_tool_condition_acknowledged_bwv,
                preexisting_conditions_noted_bwv,
                is_responsibility_accepted_bwv,
                is_liability_waiver_accepted_bwv,
                is_insurance_reminder_shown_bwv,
                ip_address_bwv,
                user_agent_bwv
            ) VALUES (
                :borrow_id,
                :waiver_type_id,
                :account_id,
                TRUE,
                :conditions,
                TRUE,
                TRUE,
                TRUE,
                :ip,
                :ua
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':waiver_type_id', $waiverTypeId, PDO::PARAM_INT);
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(
            ':conditions',
            $preexistingConditions,
            $preexistingConditions === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
        );
        $stmt->bindValue(':ip', $ipAddress, PDO::PARAM_STR);
        $stmt->bindValue(':ua', $userAgent, PDO::PARAM_STR);
        $stmt->execute();
    }
}
