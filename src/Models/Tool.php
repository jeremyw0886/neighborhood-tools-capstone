<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Tool
{
    /**
     * Fetch popular/featured tools with primary image, avg rating, and owner info.
     *
     * @param  int   $limit  Number of tools to return
     * @return array
     */
    public static function getFeatured(int $limit = 6): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                td.id_tol,
                td.tool_name_tol,
                td.primary_image,
                td.avg_rating,
                td.owner_name,
                aim.file_name_aim AS owner_avatar
            FROM tool_detail_v td
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = td.owner_id AND aim.is_primary_aim = 1
            WHERE td.availability_status = 'AVAILABLE'
            ORDER BY td.avg_rating DESC, td.created_at_tol DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
