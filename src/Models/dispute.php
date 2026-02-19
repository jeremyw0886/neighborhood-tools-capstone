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

    /**
     * Create a dispute and its initial message in a single transaction.
     *
     * Inserts into dispute_dsp (status = 'open') then into
     * dispute_message_dsm (type = 'initial_report'). The DB trigger
     * trg_dispute_before_insert validates the reporter is a borrow
     * participant; if not, the SQLSTATE 45000 propagates as a PDOException.
     *
     * @return int  The new dispute's primary key (id_dsp)
     */
    public static function create(int $borrowId, int $reporterId, string $subject, string $message): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $disputeSql = "
                INSERT INTO dispute_dsp (id_bor_dsp, id_acc_dsp, subject_text_dsp, id_dst_dsp)
                VALUES (
                    :borrow_id,
                    :reporter_id,
                    :subject,
                    (SELECT id_dst FROM dispute_status_dst WHERE status_name_dst = 'open')
                )
            ";

            $stmt = $pdo->prepare($disputeSql);
            $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
            $stmt->bindValue(':reporter_id', $reporterId, PDO::PARAM_INT);
            $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
            $stmt->execute();

            $disputeId = (int) $pdo->lastInsertId();

            $messageSql = "
                INSERT INTO dispute_message_dsm (id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm)
                VALUES (
                    :dispute_id,
                    :author_id,
                    (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = 'initial_report'),
                    :message
                )
            ";

            $stmt = $pdo->prepare($messageSql);
            $stmt->bindValue(':dispute_id', $disputeId, PDO::PARAM_INT);
            $stmt->bindValue(':author_id', $reporterId, PDO::PARAM_INT);
            $stmt->bindValue(':message', $message, PDO::PARAM_STR);
            $stmt->execute();

            $pdo->commit();

            return $disputeId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
