<?php

declare(strict_types=1);

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
                av.id_tol,
                av.tool_name_tol,
                av.primary_image,
                COALESCE(rs.avg_rating, 0) AS avg_rating,
                av.owner_name,
                aim.file_name_aim AS owner_avatar
            FROM available_tool_v av
            LEFT JOIN (
                SELECT id_tol_trt,
                       ROUND(AVG(score_trt), 1) AS avg_rating
                FROM tool_rating_trt
                GROUP BY id_tol_trt
            ) rs ON av.id_tol = rs.id_tol_trt
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = av.owner_id AND aim.is_primary_aim = 1
            ORDER BY avg_rating DESC, av.created_at_tol DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Search available tools via the stored procedure.
     *
     * Calls sp_search_available_tools() which applies FULLTEXT search,
     * zip/category/fee filters, and availability checks (no active borrow,
     * no availability block, owner not deleted).
     *
     * @param  string  $term        Search term (FULLTEXT against name + description)
     * @param  ?int    $categoryId  Filter by category, or null for all
     * @param  ?string $zip         Filter by owner zip code, or null for all
     * @param  ?float  $maxFee      Maximum rental fee, or null for no cap
     * @param  int     $limit       Results per page (SP caps at 100, defaults to 20)
     * @param  int     $offset      Pagination offset
     * @return array
     */
    public static function search(
        string $term = '',
        ?int $categoryId = null,
        ?string $zip = null,
        ?float $maxFee = null,
        int $limit = 12,
        int $offset = 0,
    ): array {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('CALL sp_search_available_tools(:term, :zip, :category, :maxFee, :limit, :offset)');

        // Bind NULL explicitly with PDO::PARAM_NULL when a filter is inactive
        $searchTerm = $term !== '' ? $term : null;

        $stmt->bindValue(':term', $searchTerm, $searchTerm === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':zip', $zip, $zip === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':category', $categoryId, $categoryId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':maxFee', $maxFee, $maxFee === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll();

        // MySQL SPs return multiple result sets (data + status). Consume all
        // of them so the connection is clean for the next query. Without this,
        // subsequent queries on the same PDO connection fail with
        // "Cannot execute queries while other unbuffered queries are active."
        $stmt->closeCursor();

        return $results;
    }

    /**
     * Count total matching tools for the same filters search() uses.
     *
     * The SP doesn't return a total count, so this mirrors its WHERE logic
     * against the base tables for accurate pagination. Uses FULLTEXT MATCH
     * for search-term filtering to stay consistent with the SP.
     *
     * @param  string  $term        Search term (FULLTEXT against name + description)
     * @param  ?int    $categoryId  Filter by category, or null for all
     * @param  ?string $zip         Filter by owner zip code, or null for all
     * @param  ?float  $maxFee      Maximum rental fee, or null for no cap
     * @return int
     */
    public static function searchCount(
        string $term = '',
        ?int $categoryId = null,
        ?string $zip = null,
        ?float $maxFee = null,
    ): int {
        $pdo = Database::connection();

        $where = [
            't.is_available_tol = TRUE',
            'a.id_ast_acc != fn_get_account_status_id(:deleted_status)',
            'NOT EXISTS (
                SELECT 1 FROM borrow_bor b
                WHERE b.id_tol_bor = t.id_tol
                  AND b.id_bst_bor IN (
                      fn_get_borrow_status_id(:bs_requested),
                      fn_get_borrow_status_id(:bs_approved),
                      fn_get_borrow_status_id(:bs_borrowed)
                  )
            )',
            'NOT EXISTS (
                SELECT 1 FROM availability_block_avb avb
                WHERE avb.id_tol_avb = t.id_tol
                  AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
            )',
        ];

        $joins = [
            'JOIN account_acc a ON t.id_acc_tol = a.id_acc',
        ];

        $searchTerm = $term !== '' ? $term : null;

        if ($searchTerm !== null) {
            $where[] = 'MATCH(t.tool_name_tol, t.tool_description_tol) AGAINST(:term IN NATURAL LANGUAGE MODE)';
        }

        if ($zip !== null) {
            $where[] = 'a.zip_code_acc = :zip';
        }

        if ($categoryId !== null) {
            $joins[] = 'JOIN tool_category_tolcat tc ON t.id_tol = tc.id_tol_tolcat';
            $where[] = 'tc.id_cat_tolcat = :category';
        }

        if ($maxFee !== null) {
            $where[] = 't.rental_fee_tol <= :maxFee';
        }

        $sql = 'SELECT COUNT(DISTINCT t.id_tol) '
             . 'FROM tool_tol t '
             . implode(' ', $joins) . ' '
             . 'WHERE ' . implode(' AND ', $where);

        $stmt = $pdo->prepare($sql);

        // Always-bound status-name parameters for the helper functions
        $stmt->bindValue(':deleted_status', 'deleted', PDO::PARAM_STR);
        $stmt->bindValue(':bs_requested', 'requested', PDO::PARAM_STR);
        $stmt->bindValue(':bs_approved', 'approved', PDO::PARAM_STR);
        $stmt->bindValue(':bs_borrowed', 'borrowed', PDO::PARAM_STR);

        if ($searchTerm !== null) {
            $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
        }

        if ($zip !== null) {
            $stmt->bindValue(':zip', $zip, PDO::PARAM_STR);
        }

        if ($categoryId !== null) {
            $stmt->bindValue(':category', $categoryId, PDO::PARAM_INT);
        }

        if ($maxFee !== null) {
            $stmt->bindValue(':maxFee', $maxFee, PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch all categories with tool counts and fee ranges.
     *
     * Returns every column from category_summary_v — callers can use
     * available_tools for counts (e.g. "Power Tools (14)") and
     * min/max_rental_fee for dynamic slider ranges.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT *
            FROM category_summary_v
            ORDER BY category_name_cat ASC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Fetch tools owned by a specific user, with primary image and avatar.
     *
     * Used on the dashboard lender view and profile pages to show a user's
     * listed tools. Results are sorted newest-first.
     *
     * @param  int $ownerId  Account ID of the tool owner
     * @param  int $limit    Max results per page
     * @param  int $offset   Pagination offset
     * @return array
     */
    public static function getByOwner(int $ownerId, int $limit = 12, int $offset = 0): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                td.id_tol,
                td.tool_name_tol,
                td.primary_image,
                td.avg_rating,
                td.rating_count,
                td.availability_status,
                td.rental_fee_tol,
                td.tool_condition,
                td.owner_name,
                aim.file_name_aim AS owner_avatar
            FROM tool_detail_v td
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = td.owner_id AND aim.is_primary_aim = 1
            WHERE td.owner_id = :ownerId
            ORDER BY td.created_at_tol DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ownerId', $ownerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count total tools owned by a specific user.
     *
     * Used for dashboard summary cards and pagination on owner tool listings.
     */
    public static function getCountByOwner(int $ownerId): int
    {
        $pdo = Database::connection();

        $sql = "
            SELECT COUNT(*)
            FROM tool_detail_v
            WHERE owner_id = :ownerId
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ownerId', $ownerId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch full tool detail by ID, including owner avatar.
     *
     * Queries tool_detail_v (which provides all tool columns, owner info,
     * ratings, borrow counts, categories, and availability status) and
     * supplements with a LEFT JOIN to account_image_aim for the owner's
     * primary avatar — the view doesn't include this.
     *
     * @param  int $id  Tool primary key
     * @return ?array    Tool data or null if not found
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                td.*,
                aim.file_name_aim AS owner_avatar
            FROM tool_detail_v td
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = td.owner_id AND aim.is_primary_aim = 1
            WHERE td.id_tol = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $tool = $stmt->fetch();

        return $tool !== false ? $tool : null;
    }
}
