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
}
