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
}
