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
     * Toggle a bookmark on or off for a user–tool pair.
     *
     * If the bookmark exists it is deleted (unbookmark); otherwise a new
     * row is inserted (bookmark). The UNIQUE constraint on
     * (id_acc_acctol, id_tol_acctol) prevents duplicates.
     *
     * @param  int  $userId  Account ID of the user
     * @param  int  $toolId  Tool primary key
     * @return bool          TRUE if the tool is now bookmarked, FALSE if removed
     */
    public static function toggle(int $userId, int $toolId): bool
    {
        $pdo = Database::connection();

        // Attempt to remove an existing bookmark
        $stmt = $pdo->prepare("
            DELETE FROM tool_bookmark_acctol
            WHERE id_acc_acctol = :user AND id_tol_acctol = :tool
        ");

        $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false; // Was bookmarked → now removed
        }

        // No existing bookmark — create one
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tool_bookmark_acctol (id_acc_acctol, id_tol_acctol)
                VALUES (:user, :tool)
            ");

            $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
            $stmt->execute();

            return true; // Now bookmarked
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return true; // Concurrent insert won the race — tool is bookmarked
            }
            throw $e;
        }
    }

    /**
     * Check whether a user has bookmarked a specific tool.
     *
     * @param  int  $userId  Account ID
     * @param  int  $toolId  Tool primary key
     * @return bool
     */
    public static function isBookmarked(int $userId, int $toolId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT 1
            FROM tool_bookmark_acctol
            WHERE id_acc_acctol = :user AND id_tol_acctol = :tool
            LIMIT 1
        ");

        $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Get all bookmarked tool IDs for a user.
     *
     * Lightweight query against the junction table (no view join)
     * for powering the active-state bookmark icon in tool cards.
     *
     * @param  int   $userId  Account ID
     * @return int[]           Flat array of tool PKs
     */
    public static function getToolIdsForUser(int $userId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT id_tol_acctol
            FROM tool_bookmark_acctol
            WHERE id_acc_acctol = :userId
        ");

        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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
