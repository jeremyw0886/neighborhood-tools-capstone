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
                t.id_tol,
                t.tool_name_tol,
                tim.file_name_tim   AS primary_image,
                COALESCE(AVG(trt.score_trt), 0) AS avg_rating,
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS owner_name,
                aim.file_name_aim   AS owner_avatar
            FROM tool_tol t
            JOIN account_acc a
                ON a.id_acc = t.id_acc_tol
            LEFT JOIN tool_image_tim tim
                ON tim.id_tol_tim = t.id_tol AND tim.is_primary_tim = 1
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = a.id_acc AND aim.is_primary_aim = 1
            LEFT JOIN tool_rating_trt trt
                ON trt.id_tol_trt = t.id_tol
            WHERE t.is_available_tol = 1
            GROUP BY t.id_tol
            ORDER BY avg_rating DESC, t.created_at_tol DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
