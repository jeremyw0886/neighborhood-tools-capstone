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
                av.rental_fee_tol,
                av.primary_image,
                COALESCE(rs.avg_rating, 0) AS avg_rating,
                av.owner_name,
                aim.file_name_aim AS owner_avatar,
                (SELECT MIN(c.category_name_cat)
                 FROM tool_category_tolcat tc
                 JOIN category_cat c ON tc.id_cat_tolcat = c.id_cat
                 WHERE tc.id_tol_tolcat = av.id_tol) AS category_name
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
                aim.file_name_aim AS owner_avatar,
                (SELECT MIN(c.category_name_cat)
                 FROM tool_category_tolcat tc
                 JOIN category_cat c ON tc.id_cat_tolcat = c.id_cat
                 WHERE tc.id_tol_tolcat = td.id_tol) AS category_name
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
     * Create a new tool listing with category assignment and optional image.
     *
     * Performs up to 3 INSERTs in a transaction:
     *   1. tool_tol — the tool record (condition defaults to 'good')
     *   2. tool_category_tolcat — junction row linking tool to its category
     *   3. tool_image_tim — primary image row (only if an image was uploaded)
     *
     * @param  array{tool_name: string, description: ?string, rental_fee: float,
     *               owner_id: int, category_id: int, image_filename: ?string} $data
     * @return int  The new tool's primary key (id_tol)
     */
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $sql = "
                INSERT INTO tool_tol (
                    tool_name_tol,
                    tool_description_tol,
                    rental_fee_tol,
                    id_acc_tol,
                    id_tcd_tol
                ) VALUES (
                    :name,
                    :description,
                    :fee,
                    :owner,
                    (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = 'good')
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':name', $data['tool_name'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], $data['description'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fee', $data['rental_fee'], PDO::PARAM_STR);
            $stmt->bindValue(':owner', $data['owner_id'], PDO::PARAM_INT);
            $stmt->execute();

            $toolId = (int) $pdo->lastInsertId();

            // Category junction
            $stmt = $pdo->prepare("
                INSERT INTO tool_category_tolcat (id_tol_tolcat, id_cat_tolcat)
                VALUES (:tool, :category)
            ");
            $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
            $stmt->bindValue(':category', $data['category_id'], PDO::PARAM_INT);
            $stmt->execute();

            // Primary image (optional)
            if ($data['image_filename'] !== null) {
                $stmt = $pdo->prepare("
                    INSERT INTO tool_image_tim (id_tol_tim, file_name_tim, is_primary_tim)
                    VALUES (:tool, :filename, TRUE)
                ");
                $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
                $stmt->bindValue(':filename', $data['image_filename'], PDO::PARAM_STR);
                $stmt->execute();
            }

            $pdo->commit();

            return $toolId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing tool listing, its category, and optionally its image.
     *
     * Performs up to 4 statements in a transaction:
     *   1. UPDATE tool_tol — name, description, fee
     *   2. DELETE tool_category_tolcat — remove old category link
     *   3. INSERT tool_category_tolcat — assign new category
     *   4. (conditional) DELETE old + INSERT new tool_image_tim row
     *
     * @param  int   $toolId  Tool primary key
     * @param  array{tool_name: string, description: ?string, rental_fee: float,
     *               category_id: int, image_filename: ?string} $data
     */
    public static function update(int $toolId, array $data): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            // 1. Update core tool columns
            $stmt = $pdo->prepare("
                UPDATE tool_tol SET
                    tool_name_tol        = :name,
                    tool_description_tol = :description,
                    rental_fee_tol       = :fee
                WHERE id_tol = :id
            ");

            $stmt->bindValue(':name', $data['tool_name'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], $data['description'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fee', $data['rental_fee'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $toolId, PDO::PARAM_INT);
            $stmt->execute();

            // 2–3. Replace category junction (delete + insert)
            $stmt = $pdo->prepare("
                DELETE FROM tool_category_tolcat
                WHERE id_tol_tolcat = :tool
            ");
            $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("
                INSERT INTO tool_category_tolcat (id_tol_tolcat, id_cat_tolcat)
                VALUES (:tool, :category)
            ");
            $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
            $stmt->bindValue(':category', $data['category_id'], PDO::PARAM_INT);
            $stmt->execute();

            // 4. Replace primary image (only if a new file was uploaded)
            if ($data['image_filename'] !== null) {
                $stmt = $pdo->prepare("
                    DELETE FROM tool_image_tim
                    WHERE id_tol_tim = :tool AND is_primary_tim = TRUE
                ");
                $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $pdo->prepare("
                    INSERT INTO tool_image_tim (id_tol_tim, file_name_tim, is_primary_tim)
                    VALUES (:tool, :filename, TRUE)
                ");
                $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
                $stmt->bindValue(':filename', $data['image_filename'], PDO::PARAM_STR);
                $stmt->execute();
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Soft-delete a tool by clearing its availability flag.
     *
     * Sets is_available_tol = FALSE so the tool drops out of
     * available_tool_v and search results. Historical borrows,
     * ratings, and images are preserved.
     *
     * @param  int  $toolId  Tool primary key
     */
    public static function softDelete(int $toolId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE tool_tol
            SET is_available_tol = FALSE
            WHERE id_tol = :id
        ");

        $stmt->bindValue(':id', $toolId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Fetch the primary category ID for a tool.
     *
     * The tool_detail_v view returns category names as a GROUP_CONCAT string,
     * which isn't useful for pre-selecting a <select>. This queries the
     * junction table directly for the first assigned category ID.
     *
     * @param  int  $toolId  Tool primary key
     * @return ?int          Category ID, or null if none assigned
     */
    public static function getCategoryId(int $toolId): ?int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT id_cat_tolcat
            FROM tool_category_tolcat
            WHERE id_tol_tolcat = :toolId
            LIMIT 1
        ");

        $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
        $stmt->execute();

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /**
     * Fetch the primary category name for a batch of tool IDs.
     *
     * Returns an associative array keyed by tool ID. Tools with no
     * category assignment are omitted from the result.
     *
     * @param  int[] $toolIds  Tool primary keys
     * @return array<int, string>  [id_tol => category_name_cat]
     */
    public static function getCategoryNamesForTools(array $toolIds): array
    {
        if ($toolIds === []) {
            return [];
        }

        $pdo = Database::connection();

        $placeholders = implode(',', array_fill(0, count($toolIds), '?'));

        $stmt = $pdo->prepare("
            SELECT tc.id_tol_tolcat, MIN(c.category_name_cat) AS category_name
            FROM tool_category_tolcat tc
            JOIN category_cat c ON tc.id_cat_tolcat = c.id_cat
            WHERE tc.id_tol_tolcat IN ({$placeholders})
            GROUP BY tc.id_tol_tolcat
        ");

        foreach (array_values($toolIds) as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }

        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['id_tol_tolcat']] = $row['category_name'];
        }

        return $map;
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
