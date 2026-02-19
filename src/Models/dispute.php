<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Dispute
{
    private const int PER_PAGE = 12;

    /**
     * Fetch open disputes with pagination.
     *
     * Queries open_dispute_v which joins dispute_dsp, account_acc (reporter,
     * borrower, lender), borrow_bor, tool_tol, dispute_message_dsm,
     * incident_report_irt, and security_deposit_sdp.
     *
     * @return array Rows with subject, reporter/borrower/lender info,
     *               message count, related incidents, deposit details
     */
    public static function getAll(int $limit = self::PER_PAGE, int $offset = 0): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM open_dispute_v
            ORDER BY created_at_dsp DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count total open disputes for pagination.
     */
    public static function getCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query('SELECT COUNT(*) FROM open_dispute_v')->fetchColumn();
    }

    /**
     * Find a single open dispute by its primary key.
     *
     * @return array|false  Dispute row or false if not found / not open
     */
    public static function findById(int $disputeId): array|false
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM open_dispute_v
            WHERE id_dsp = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $disputeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Check whether an open dispute already exists for a borrow.
     *
     * Prevents duplicate filings — the trigger allows the INSERT but
     * the user experience is cleaner when we catch it before submission.
     */
    public static function hasOpenDispute(int $borrowId): bool
    {
        $pdo = Database::connection();

        $sql = "
            SELECT COUNT(*)
            FROM open_dispute_v
            WHERE id_bor_dsp = :bor_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':bor_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }
}
