<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Bookmark
{
    /**
     * Fetch bookmarked tools for a user, newest bookmarks first.
     *
     * Queries user_bookmarks_v and supplements with the owner's
     * primary avatar (not included in the view). Column aliases
     * ensure compatibility with the tool-card partial.
     *
     * @param  int $userId  Account ID of the bookmark owner
     * @param  int $limit   Max results per page
     * @param  int $offset  Pagination offset
     * @return array
     */
    public static function getForUser(int $userId, int $limit = 12, int $offset = 0): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                bv.bookmark_id,
                bv.tool_id AS id_tol,
                bv.tool_name_tol,
                bv.tool_description_tol,
                bv.rental_fee_tol,
                bv.tool_condition,
                bv.primary_image,
                bv.owner_id,
                bv.owner_name,
                bv.owner_neighborhood,
                bv.availability_status,
                bv.avg_rating,
                bv.rating_count,
                bv.bookmarked_at,
                aim.file_name_aim AS owner_avatar
            FROM user_bookmarks_v bv
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = bv.owner_id AND aim.is_primary_aim = 1
            WHERE bv.user_id = :userId
            ORDER BY bv.bookmarked_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count total bookmarks for a user.
     *
     * Used for pagination on the bookmarks page.
     */
    public static function getCountForUser(int $userId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM user_bookmarks_v
            WHERE user_id = :userId
        ");

        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
