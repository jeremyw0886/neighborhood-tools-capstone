<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Handover
{
    /**
     * Find a pending (unverified) handover for a borrow by querying pending_handover_v.
     *
     * Returns the most recent pending handover for the given borrow,
     * including code status (ACTIVE, EXPIRING SOON, EXPIRED), party
     * names, tool info, and the handover type (pickup/return).
     */
    public static function findPendingByBorrowId(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            SELECT *
            FROM pending_handover_v
            WHERE id_bor_hov = :borrow_id
            ORDER BY generated_at_hov DESC
            LIMIT 1
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
