<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Handover-code persistence for the pickup and return flow.
 *
 * Each row in `handover_verification_hov` is a six-character code with a
 * 24-hour expiry that the counterparty enters to confirm a pickup or
 * return. This model handles `create()` (with a duplicate-pending guard
 * per borrow + type), code retrieval, the pending-handover lookup
 * (single + bulk via `pending_handover_v`), expiry deletion, and the
 * final `markVerified()` after the counterparty enters the code.
 */
class Handover
{
    /**
     * Create a handover record for a borrow.
     *
     * Refuses to create a second active (unverified, unexpired) handover for
     * the same borrow + type combination, so a duplicate POST or a controller
     * pre-check race can't mint a parallel pending code. The caller can rely
     * on the throw to surface duplicate-generation attempts.
     *
     * @param  string $type  Handover type name ('pickup' or 'return')
     * @return int    The new handover ID
     * @throws \RuntimeException When an active handover of this type already exists for the borrow
     */
    public static function create(int $borrowId, int $generatorId, string $type): int
    {
        $pdo = Database::connection();

        if (self::hasActivePendingForType($pdo, $borrowId, $type)) {
            throw new \RuntimeException(
                "Handover::create — active {$type} handover already exists for borrow {$borrowId}",
            );
        }

        $stmt = $pdo->prepare('
            INSERT INTO handover_verification_hov (id_bor_hov, id_hot_hov, id_acc_generator_hov)
            VALUES (:borrow_id, fn_get_handover_type_id(:type), :generator_id)
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':generator_id', $generatorId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /**
     * Check whether an active (unverified, unexpired) handover of `$type` exists for `$borrowId`.
     */
    private static function hasActivePendingForType(\PDO $pdo, int $borrowId, string $type): bool
    {
        $stmt = $pdo->prepare('
            SELECT 1
            FROM handover_verification_hov hov
            JOIN handover_type_hot         hot ON hot.id_hot = hov.id_hot_hov
            WHERE hov.id_bor_hov         = :borrow_id
              AND hot.type_name_hot      = :type
              AND hov.verified_at_hov    IS NULL
              AND hov.expires_at_hov     > NOW()
            LIMIT 1
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Retrieve the verification code for a handover by its ID.
     */
    public static function getCodeById(int $handoverId): ?string
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            SELECT verification_code_hov
            FROM handover_verification_hov
            WHERE id_hov = :id
        ');

        $stmt->bindValue(':id', $handoverId, PDO::PARAM_INT);
        $stmt->execute();

        $code = $stmt->fetchColumn();

        return $code !== false ? $code : null;
    }

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
     * Find pending handovers for multiple borrows in one query.
     *
     * @param  int[] $borrowIds
     * @return array<int, array> Keyed by borrow ID
     */
    public static function findPendingByBorrowIds(array $borrowIds): array
    {
        if ($borrowIds === []) {
            return [];
        }

        $pdo = Database::connection();

        $placeholders = implode(',', array_fill(0, count($borrowIds), '?'));

        $stmt = $pdo->prepare("
            SELECT *
            FROM pending_handover_v
            WHERE id_bor_hov IN ({$placeholders})
            ORDER BY generated_at_hov DESC
        ");

        foreach (array_values($borrowIds) as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }

        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch()) {
            $borrowId = (int) $row['id_bor_hov'];
            if (!isset($result[$borrowId])) {
                $result[$borrowId] = $row;
            }
        }

        return $result;
    }

    /**
     * Delete an expired, unverified handover so a fresh one can be created.
     */
    public static function expireHandover(int $handoverId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            DELETE FROM handover_verification_hov
            WHERE id_hov = :id
              AND verified_at_hov IS NULL
              AND expires_at_hov < NOW()
        ');

        $stmt->bindValue(':id', $handoverId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Mark a pending handover as verified.
     *
     * Sets verified_at_hov to NOW() and records the verifier's account ID.
     * Optionally updates condition_notes_hov with the verifier's notes.
     * Only updates rows that have not already been verified (verified_at IS NULL).
     *
     * @param  ?string $conditionNotes  Verifier's condition notes (null = leave existing notes unchanged)
     * @return bool    True if a row was updated, false if already verified or not found
     */
    public static function markVerified(int $handoverId, int $verifierId, ?string $conditionNotes = null): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            UPDATE handover_verification_hov
            SET verified_at_hov      = NOW(),
                id_acc_verifier_hov  = :verifier_id,
                condition_notes_hov  = COALESCE(:notes, condition_notes_hov)
            WHERE id_hov = :handover_id
              AND verified_at_hov IS NULL
        ');

        $stmt->bindValue(':verifier_id', $verifierId, PDO::PARAM_INT);
        $stmt->bindValue(':notes', $conditionNotes, $conditionNotes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':handover_id', $handoverId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
