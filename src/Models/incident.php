<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Incident
{
    private const int PER_PAGE = 12;

    /**
     * Fetch paginated open incidents for the admin incidents page.
     *
     * Queries open_incident_v which joins incident_report_irt, incident_type_ity,
     * borrow_bor, tool_tol, and account_acc for reporter/borrower/lender context,
     * deposit info, and related dispute counts.
     *
     * @return array  Rows from open_incident_v
     */
    public static function getOpen(int $limit = self::PER_PAGE, int $offset = 0): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM open_incident_v
            ORDER BY created_at_irt DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count open (unresolved) incidents platform-wide.
     */
    public static function getOpenCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query('SELECT COUNT(*) FROM open_incident_v')->fetchColumn();
    }

    /**
     * Fetch all incident type options from the lookup table.
     *
     * @return array  Rows with id_ity and type_name_ity
     */
    public static function getTypes(): array
    {
        $pdo = Database::connection();

        return $pdo->query('SELECT id_ity, type_name_ity FROM incident_type_ity ORDER BY id_ity')
                    ->fetchAll();
    }

    /**
     * Check whether an unresolved incident already exists for a borrow.
     *
     * Queries open_incident_v which filters on resolved_at_irt IS NULL.
     */
    public static function hasOpenIncident(int $borrowId): bool
    {
        $pdo = Database::connection();

        $sql = "
            SELECT COUNT(*)
            FROM open_incident_v
            WHERE id_bor_irt = :bor_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':bor_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Fetch a single incident by primary key regardless of resolution status.
     *
     * Mirrors the open_incident_v join structure but without the
     * resolved_at_irt IS NULL filter so both open and resolved incidents
     * are viewable on the detail page. Includes deposit info and
     * related dispute count.
     *
     * @return array|null  Incident row with full context, or null
     */
    public static function findByIdWithContext(int $incidentId): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                irt.id_irt,
                irt.subject_irt,
                irt.description_irt,
                ity.type_name_ity                AS incident_type,
                irt.incident_occurred_at_irt,
                irt.created_at_irt,
                TIMESTAMPDIFF(DAY, irt.created_at_irt, NOW()) AS days_open,
                irt.is_reported_within_deadline_irt,
                irt.estimated_damage_amount_irt,
                irt.resolution_notes_irt,
                irt.resolved_at_irt,
                irt.id_acc_resolved_by_irt,
                CONCAT(resolver.first_name_acc, ' ', resolver.last_name_acc) AS resolver_name,
                irt.id_acc_irt                    AS reporter_id,
                CONCAT(reporter.first_name_acc, ' ', reporter.last_name_acc) AS reporter_name,
                irt.id_bor_irt,
                t.id_tol                         AS tool_id,
                t.tool_name_tol,
                b.id_acc_bor                     AS borrower_id,
                CONCAT(borrower.first_name_acc, ' ', borrower.last_name_acc) AS borrower_name,
                t.id_acc_tol                     AS lender_id,
                CONCAT(lender.first_name_acc, ' ', lender.last_name_acc)     AS lender_name,
                COALESCE(dispute_stats.related_disputes, 0) AS related_disputes,
                sdp.id_sdp                       AS deposit_id,
                sdp.amount_sdp                   AS deposit_amount,
                dps.status_name_dps              AS deposit_status
            FROM incident_report_irt irt
            JOIN incident_type_ity ity      ON irt.id_ity_irt = ity.id_ity
            JOIN account_acc reporter       ON irt.id_acc_irt = reporter.id_acc
            JOIN borrow_bor b               ON irt.id_bor_irt = b.id_bor
            JOIN tool_tol t                 ON b.id_tol_bor   = t.id_tol
            JOIN account_acc borrower       ON b.id_acc_bor   = borrower.id_acc
            JOIN account_acc lender         ON t.id_acc_tol   = lender.id_acc
            LEFT JOIN account_acc resolver  ON irt.id_acc_resolved_by_irt = resolver.id_acc
            LEFT JOIN security_deposit_sdp sdp ON b.id_bor    = sdp.id_bor_sdp
            LEFT JOIN deposit_status_dps dps   ON sdp.id_dps_sdp = dps.id_dps
            LEFT JOIN (
                SELECT id_bor_dsp, COUNT(*) AS related_disputes
                FROM dispute_dsp
                GROUP BY id_bor_dsp
            ) dispute_stats ON irt.id_bor_irt = dispute_stats.id_bor_dsp
            WHERE irt.id_irt = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $incidentId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Fetch photos attached to an incident in sort order.
     *
     * @return array  Rows with id_iph, file_name_iph, caption_iph, sort_order_iph
     */
    public static function getPhotos(int $incidentId): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT id_iph, file_name_iph, caption_iph, sort_order_iph, created_at_iph
            FROM incident_photo_iph
            WHERE id_irt_iph = :incident_id
            ORDER BY sort_order_iph ASC, id_iph ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':incident_id', $incidentId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Create an incident report and attach photos in a single transaction.
     *
     * Inserts into incident_report_irt then into incident_photo_iph for
     * each uploaded photo. The DB trigger trg_incident_report_before_insert
     * auto-calculates is_reported_within_deadline_irt based on the 48-hour
     * window from incident_occurred_at_irt.
     *
     * @param  array<string> $photoFilenames  Filenames already moved to uploads/incidents/
     * @return int  The new incident's primary key (id_irt)
     */
    public static function create(
        int $borrowId,
        int $reporterId,
        int $incidentTypeId,
        string $subject,
        string $description,
        string $occurredAt,
        ?string $estimatedDamageAmount,
        array $photoFilenames = [],
    ): int {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $sql = "
                INSERT INTO incident_report_irt
                    (id_bor_irt, id_acc_irt, id_ity_irt, subject_irt,
                     description_irt, incident_occurred_at_irt,
                     estimated_damage_amount_irt)
                VALUES
                    (:borrow_id, :reporter_id, :type_id, :subject,
                     :description, :occurred_at,
                     :damage_amount)
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
            $stmt->bindValue(':reporter_id', $reporterId, PDO::PARAM_INT);
            $stmt->bindValue(':type_id', $incidentTypeId, PDO::PARAM_INT);
            $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':occurred_at', $occurredAt, PDO::PARAM_STR);
            $stmt->bindValue(
                ':damage_amount',
                $estimatedDamageAmount,
                $estimatedDamageAmount === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
            );
            $stmt->execute();

            $incidentId = (int) $pdo->lastInsertId();

            if ($photoFilenames !== []) {
                $photoSql = "
                    INSERT INTO incident_photo_iph
                        (id_irt_iph, file_name_iph, sort_order_iph)
                    VALUES (:incident_id, :filename, :sort_order)
                ";

                $photoStmt = $pdo->prepare($photoSql);

                foreach ($photoFilenames as $index => $filename) {
                    $photoStmt->bindValue(':incident_id', $incidentId, PDO::PARAM_INT);
                    $photoStmt->bindValue(':filename', $filename, PDO::PARAM_STR);
                    $photoStmt->bindValue(':sort_order', $index, PDO::PARAM_INT);
                    $photoStmt->execute();
                }
            }

            $pdo->commit();

            return $incidentId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
