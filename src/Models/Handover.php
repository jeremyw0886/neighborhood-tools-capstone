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

    /**
     * Mark a pending handover as verified.
     *
     * Sets verified_at_hov to NOW() and records the verifier's account ID.
     * Only updates rows that have not already been verified (verified_at IS NULL).
     *
     * @return bool  True if a row was updated, false if already verified or not found
     */
    public static function markVerified(int $handoverId, int $verifierId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            UPDATE handover_verification_hov
            SET verified_at_hov      = NOW(),
                id_acc_verifier_hov  = :verifier_id
            WHERE id_hov = :handover_id
              AND verified_at_hov IS NULL
        ');

        $stmt->bindValue(':verifier_id', $verifierId, PDO::PARAM_INT);
        $stmt->bindValue(':handover_id', $handoverId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
