<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Incident
{
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
