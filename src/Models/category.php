<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Category
{
    /**
     * Fetch all categories with tool counts, ratings, and fee ranges.
     *
     * Queries category_summary_v which computes per-category stats
     * including available/listed/total tools, avg rating, completed
     * borrows, and min/max rental fees. Includes category_icon from
     * vector_image_vec when present.
     *
     * @return array
     */
    public static function getAll(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT *
            FROM category_summary_v
            ORDER BY category_name_cat ASC
        ");

        return $stmt->fetchAll();
    }
}
