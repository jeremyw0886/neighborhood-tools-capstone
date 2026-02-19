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
     * Fetch a single dispute by primary key regardless of status.
     *
     * Unlike findById() which queries open_dispute_v (open only),
     * this method joins the raw tables so resolved/dismissed disputes
     * remain viewable on the detail page.
     *
     * @return array|null  Dispute row with participant context, or null
     */
    public static function findByIdWithContext(int $disputeId): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                d.id_dsp,
                d.subject_text_dsp,
                d.created_at_dsp,
                d.resolved_at_dsp,
                dst.status_name_dst   AS dispute_status,
                d.id_acc_dsp          AS reporter_id,
                CONCAT(reporter.first_name_acc, ' ', reporter.last_name_acc) AS reporter_name,
                d.id_bor_dsp,
                t.tool_name_tol,
                b.id_acc_bor          AS borrower_id,
                CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
                t.id_acc_tol          AS lender_id,
                CONCAT(lender.first_name_acc, ' ', lender.last_name_acc)     AS lender_name,
                TIMESTAMPDIFF(DAY, d.created_at_dsp, NOW())                  AS days_open
            FROM dispute_dsp d
            JOIN dispute_status_dst dst ON d.id_dst_dsp = dst.id_dst
            JOIN account_acc reporter   ON d.id_acc_dsp = reporter.id_acc
            JOIN borrow_bor b           ON d.id_bor_dsp = b.id_bor
            JOIN tool_tol t             ON b.id_tol_bor = t.id_tol
            JOIN account_acc borrower   ON b.id_acc_bor = borrower.id_acc
            JOIN account_acc lender     ON t.id_acc_tol = lender.id_acc
            WHERE d.id_dsp = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $disputeId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Fetch all messages for a dispute in chronological order.
     *
     * Joins account_acc for author names and dispute_message_type_dmt
     * for the type label. Returns is_internal_dsm so the controller/view
     * can filter admin-only notes for non-admin viewers.
     *
     * @return array  Rows with author info, type label, text, timestamps
     */
    public static function getMessages(int $disputeId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                m.id_dsm,
                m.message_text_dsm,
                m.is_internal_dsm,
                m.created_at_dsm,
                m.id_acc_dsm          AS author_id,
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS author_name,
                a.avatar_filename_acc AS author_avatar,
                dmt.type_name_dmt     AS message_type
            FROM dispute_message_dsm m
            JOIN account_acc a                ON m.id_acc_dsm = a.id_acc
            JOIN dispute_message_type_dmt dmt ON m.id_dmt_dsm = dmt.id_dmt
            WHERE m.id_dsp_dsm = :dispute_id
            ORDER BY m.created_at_dsm ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':dispute_id', $disputeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Append a message to an existing dispute thread.
     *
     * Resolves the message type by name from dispute_message_type_dmt.
     * The DB trigger trg_dispute_message_before_insert rejects deleted
     * accounts.
     *
     * @param  string $typeName  One of: response, admin_note, resolution
     */
    public static function addMessage(
        int $disputeId,
        int $authorId,
        string $typeName,
        string $text,
        bool $isInternal = false,
    ): void {
        $pdo = Database::connection();

        $sql = "
            INSERT INTO dispute_message_dsm
                (id_dsp_dsm, id_acc_dsm, id_dmt_dsm, message_text_dsm, is_internal_dsm)
            VALUES (
                :dispute_id,
                :author_id,
                (SELECT id_dmt FROM dispute_message_type_dmt WHERE type_name_dmt = :type_name),
                :message,
                :is_internal
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':dispute_id', $disputeId, PDO::PARAM_INT);
        $stmt->bindValue(':author_id', $authorId, PDO::PARAM_INT);
        $stmt->bindValue(':type_name', $typeName, PDO::PARAM_STR);
        $stmt->bindValue(':message', $text, PDO::PARAM_STR);
        $stmt->bindValue(':is_internal', $isInternal, PDO::PARAM_BOOL);
        $stmt->execute();
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
