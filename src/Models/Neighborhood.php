<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Neighborhood
{
    /**
     * Fetch all neighborhoods grouped by city, ordered alphabetically.
     *
     * Used by the registration form's neighborhood dropdown.
     *
     * @return array<int, array{id_nbh: int, neighborhood_name_nbh: string, city_name_nbh: string}>
     */
    public static function allGroupedByCity(): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT id_nbh, neighborhood_name_nbh, city_name_nbh
            FROM neighborhood_nbh
            ORDER BY city_name_nbh, neighborhood_name_nbh
        ";

        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Fetch distinct city names for the location toggle radio buttons.
     *
     * @return array<int, array{city: string}>
     */
    public static function getCities(): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT DISTINCT city_name_nbh AS city
            FROM neighborhood_nbh
            ORDER BY city_name_nbh
        ";

        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }
}
