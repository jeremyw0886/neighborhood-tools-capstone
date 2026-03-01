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
                av.owner_id,
                av.tool_name_tol,
                av.rental_fee_tol,
                av.primary_image,
                COALESCE(rs.avg_rating, 0) AS avg_rating,
                av.owner_name,
                aim.file_name_aim AS owner_avatar,
                avv.file_name_avv AS owner_vector_avatar
            FROM available_tool_v av
            LEFT JOIN (
                SELECT id_tol_trt,
                       ROUND(AVG(score_trt), 1) AS avg_rating
                FROM tool_rating_trt
                GROUP BY id_tol_trt
            ) rs ON av.id_tol = rs.id_tol_trt
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = av.owner_id AND aim.is_primary_aim = 1
            LEFT JOIN account_acc acc_avv ON av.owner_id = acc_avv.id_acc
            LEFT JOIN avatar_vector_avv avv ON acc_avv.id_avv_acc = avv.id_avv
            ORDER BY avg_rating DESC, av.created_at_tol DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll();

        $toolIds = array_column($results, 'id_tol');
        $categoryMap = self::getCategoryDataForTools($toolIds);

        foreach ($results as &$row) {
            $catData = $categoryMap[(int) $row['id_tol']] ?? [];
            $row['category_name'] = $catData['category_name'] ?? null;
            $row['category_icon'] = $catData['category_icon'] ?? null;
        }
        unset($row);

        return $results;
    }

    /** Meters per mile for ST_Distance_Sphere conversion. */
    private const float METERS_PER_MILE = 1609.344;

    /**
     * Search available tools via stored procedure or spatial query.
     *
     * When $radius is null the SP handles all filtering. When a radius is
     * provided (and $zip is set), a direct spatial query replaces the SP
     * so tools within the given mile radius are returned.
     *
     * @param  string  $term        Search term (FULLTEXT against name + description)
     * @param  ?int    $categoryId  Filter by category, or null for all
     * @param  ?string $zip         Filter by owner zip code, or null for all
     * @param  ?float  $maxFee      Maximum rental fee, or null for no cap
     * @param  int     $limit       Results per page
     * @param  int     $offset      Pagination offset
     * @param  ?int    $radius      Search radius in miles, or null for exact ZIP
     * @return array
     */
    public static function search(
        string $term = '',
        ?int $categoryId = null,
        ?string $zip = null,
        ?float $maxFee = null,
        int $limit = 12,
        int $offset = 0,
        ?int $radius = null,
    ): array {
        if ($radius !== null && $zip !== null) {
            return self::searchByDistance($term, $categoryId, $zip, $maxFee, $limit, $offset, $radius);
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare('CALL sp_search_available_tools(:term, :zip, :category, :maxFee, :limit, :offset)');

        $searchTerm = $term !== '' ? $term : null;

        $stmt->bindValue(':term', $searchTerm, $searchTerm === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':zip', $zip, $zip === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':category', $categoryId, $categoryId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':maxFee', $maxFee, $maxFee === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll();

        $stmt->closeCursor();

        return self::enrichResults($pdo, $results);
    }

    /**
     * Spatial search returning tools within $radius miles of $originZip.
     *
     * Mirrors the SP's availability/status checks but replaces exact ZIP
     * match with ST_Distance_Sphere against zip_code_zpc spatial indexes.
     *
     * @return array
     */
    private static function searchByDistance(
        string $term,
        ?int $categoryId,
        string $originZip,
        ?float $maxFee,
        int $limit,
        int $offset,
        int $radius,
    ): array {
        $pdo = Database::connection();

        $searchTerm = $term !== '' ? $term : null;

        $select = "
            SELECT
                t.id_tol,
                t.tool_name_tol,
                t.tool_description_tol,
                t.rental_fee_tol,
                t.default_loan_duration_hours_tol,
                t.is_deposit_required_tol,
                t.default_deposit_amount_tol,
                t.id_acc_tol AS owner_id,
                tcd.condition_name_tcd AS tool_condition,
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS owner_name,
                a.zip_code_acc AS owner_zip,
                aim.file_name_aim AS owner_avatar,
                avv.file_name_avv AS owner_vector_avatar,
                tim.file_name_tim AS primary_image,
                (SELECT COALESCE(AVG(trt.score_trt), 0)
                   FROM tool_rating_trt trt
                  WHERE trt.id_tol_trt = t.id_tol) AS avg_rating,
                (SELECT COUNT(*)
                   FROM tool_rating_trt trt
                  WHERE trt.id_tol_trt = t.id_tol) AS rating_count,
                ROUND(
                    ST_Distance_Sphere(z.location_point_zpc, origin.location_point_zpc)
                    / :mpm_select,
                    1
                ) AS distance_miles
        ";

        $joins = "
            FROM tool_tol t
            JOIN account_acc a ON t.id_acc_tol = a.id_acc
            JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
            JOIN zip_code_zpc z ON z.zip_code_zpc = a.zip_code_acc
            CROSS JOIN zip_code_zpc origin
            LEFT JOIN tool_image_tim tim
                   ON t.id_tol = tim.id_tol_tim AND tim.is_primary_tim = TRUE
            LEFT JOIN account_image_aim aim
                   ON aim.id_acc_aim = t.id_acc_tol AND aim.is_primary_aim = 1
            LEFT JOIN avatar_vector_avv avv ON a.id_avv_acc = avv.id_avv
        ";

        if ($categoryId !== null) {
            $joins .= " JOIN tool_category_tolcat tc ON t.id_tol = tc.id_tol_tolcat";
        }

        $where = "
            WHERE origin.zip_code_zpc = :origin_zip
              AND t.is_available_tol = TRUE
              AND a.id_ast_acc != fn_get_account_status_id(:deleted_status)
              AND NOT EXISTS (
                  SELECT 1 FROM borrow_bor b
                   WHERE b.id_tol_bor = t.id_tol
                     AND b.id_bst_bor IN (
                         fn_get_borrow_status_id(:bs_requested),
                         fn_get_borrow_status_id(:bs_approved),
                         fn_get_borrow_status_id(:bs_borrowed)
                     )
              )
              AND NOT EXISTS (
                  SELECT 1 FROM availability_block_avb avb
                   WHERE avb.id_tol_avb = t.id_tol
                     AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
              )
        ";

        if ($searchTerm !== null) {
            $where .= " AND MATCH(t.tool_name_tol, t.tool_description_tol) AGAINST(:term IN NATURAL LANGUAGE MODE)";
        }

        if ($categoryId !== null) {
            $where .= " AND tc.id_cat_tolcat = :category";
        }

        if ($maxFee !== null) {
            $where .= " AND t.rental_fee_tol <= :maxFee";
        }

        $where .= " AND ROUND(ST_Distance_Sphere(z.location_point_zpc, origin.location_point_zpc) / :mpm_filter, 1) <= :radius";

        $sql = $select . $joins . $where
             . " ORDER BY distance_miles ASC"
             . " LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':mpm_select', self::METERS_PER_MILE);
        $stmt->bindValue(':mpm_filter', self::METERS_PER_MILE);
        $stmt->bindValue(':origin_zip', $originZip);
        $stmt->bindValue(':deleted_status', 'deleted');
        $stmt->bindValue(':bs_requested', 'requested');
        $stmt->bindValue(':bs_approved', 'approved');
        $stmt->bindValue(':bs_borrowed', 'borrowed');
        $stmt->bindValue(':radius', $radius, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($searchTerm !== null) {
            $stmt->bindValue(':term', $searchTerm);
        }

        if ($categoryId !== null) {
            $stmt->bindValue(':category', $categoryId, PDO::PARAM_INT);
        }

        if ($maxFee !== null) {
            $stmt->bindValue(':maxFee', $maxFee);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Enrich SP search results with owner_id and avatar (not returned by the SP).
     *
     * @return array
     */
    private static function enrichResults(\PDO $pdo, array $results): array
    {
        if ($results === []) {
            return $results;
        }

        $toolIds = array_column($results, 'id_tol');
        $placeholders = implode(',', array_fill(0, count($toolIds), '?'));

        $stmt = $pdo->prepare("
            SELECT t.id_tol,
                   t.id_acc_tol AS owner_id,
                   aim.file_name_aim AS owner_avatar,
                   avv.file_name_avv AS owner_vector_avatar
            FROM tool_tol t
            JOIN account_acc a ON t.id_acc_tol = a.id_acc
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = t.id_acc_tol AND aim.is_primary_aim = 1
            LEFT JOIN avatar_vector_avv avv ON a.id_avv_acc = avv.id_avv
            WHERE t.id_tol IN ({$placeholders})
        ");

        foreach ($toolIds as $i => $tid) {
            $stmt->bindValue($i + 1, $tid, PDO::PARAM_INT);
        }

        $stmt->execute();

        $enrichMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $enrichMap[(int) $row['id_tol']] = $row;
        }

        foreach ($results as &$row) {
            $extra = $enrichMap[(int) $row['id_tol']] ?? [];
            $row['owner_id']             = $extra['owner_id'] ?? null;
            $row['owner_avatar']         = $extra['owner_avatar'] ?? null;
            $row['owner_vector_avatar']  = $extra['owner_vector_avatar'] ?? null;
        }
        unset($row);

        return $results;
    }

    /**
     * Count total matching tools for the same filters search() uses.
     *
     * Mirrors the SP's WHERE logic against the base tables for accurate
     * pagination. When $radius is set, replaces exact ZIP match with
     * spatial distance filtering.
     *
     * @param  string  $term        Search term (FULLTEXT against name + description)
     * @param  ?int    $categoryId  Filter by category, or null for all
     * @param  ?string $zip         Filter by owner zip code, or null for all
     * @param  ?float  $maxFee      Maximum rental fee, or null for no cap
     * @param  ?int    $radius      Search radius in miles, or null for exact ZIP
     * @return int
     */
    public static function searchCount(
        string $term = '',
        ?int $categoryId = null,
        ?string $zip = null,
        ?float $maxFee = null,
        ?int $radius = null,
    ): int {
        $pdo = Database::connection();
        $useDistance = $radius !== null && $zip !== null;

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

        if ($useDistance) {
            $joins[] = 'JOIN zip_code_zpc z ON z.zip_code_zpc = a.zip_code_acc';
            $joins[] = 'CROSS JOIN zip_code_zpc origin';
            $where[] = 'origin.zip_code_zpc = :origin_zip';
            $where[] = 'ROUND(ST_Distance_Sphere(z.location_point_zpc, origin.location_point_zpc) / :meters_per_mile, 1) <= :radius';
        }

        $searchTerm = $term !== '' ? $term : null;

        if ($searchTerm !== null) {
            $where[] = 'MATCH(t.tool_name_tol, t.tool_description_tol) AGAINST(:term IN NATURAL LANGUAGE MODE)';
        }

        if ($zip !== null && !$useDistance) {
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

        $stmt->bindValue(':deleted_status', 'deleted', PDO::PARAM_STR);
        $stmt->bindValue(':bs_requested', 'requested', PDO::PARAM_STR);
        $stmt->bindValue(':bs_approved', 'approved', PDO::PARAM_STR);
        $stmt->bindValue(':bs_borrowed', 'borrowed', PDO::PARAM_STR);

        if ($useDistance) {
            $stmt->bindValue(':origin_zip', $zip);
            $stmt->bindValue(':meters_per_mile', self::METERS_PER_MILE);
            $stmt->bindValue(':radius', $radius, PDO::PARAM_INT);
        }

        if ($searchTerm !== null) {
            $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
        }

        if ($zip !== null && !$useDistance) {
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
     * Queries category_summary_fast_v (materialized, refreshed hourly) for
     * performant reads on the high-traffic tool browse filter bar.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT *
            FROM category_summary_fast_v
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
                td.owner_id,
                td.tool_name_tol,
                td.primary_image,
                td.avg_rating,
                td.rating_count,
                td.availability_status,
                td.rental_fee_tol,
                td.tool_condition,
                td.owner_name,
                aim.file_name_aim AS owner_avatar,
                avv.file_name_avv AS owner_vector_avatar
            FROM tool_detail_v td
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = td.owner_id AND aim.is_primary_aim = 1
            LEFT JOIN account_acc acc_avv ON td.owner_id = acc_avv.id_acc
            LEFT JOIN avatar_vector_avv avv ON acc_avv.id_avv_acc = avv.id_avv
            WHERE td.owner_id = :ownerId
            ORDER BY td.created_at_tol DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':ownerId', $ownerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll();

        $toolIds = array_column($results, 'id_tol');
        $categoryMap = self::getCategoryDataForTools($toolIds);

        foreach ($results as &$row) {
            $catData = $categoryMap[(int) $row['id_tol']] ?? [];
            $row['category_name'] = $catData['category_name'] ?? null;
            $row['category_icon'] = $catData['category_icon'] ?? null;
        }
        unset($row);

        return $results;
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
     *               owner_id: int, category_id: int, condition: string,
     *               loan_duration: ?int, image_filename: ?string} $data
     * @return int  The new tool's primary key (id_tol)
     */
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $hasDuration = $data['loan_duration'] !== null;
        $hasFuel     = ($data['fuel_type'] ?? null) !== null;

        try {
            $durationCol = $hasDuration ? ', default_loan_duration_hours_tol' : '';
            $durationVal = $hasDuration ? ', :duration' : '';
            $fuelCol     = $hasFuel ? ', id_ftp_tol' : '';
            $fuelVal     = $hasFuel ? ', (SELECT id_ftp FROM fuel_type_ftp WHERE fuel_name_ftp = :fuel_type)' : '';

            $sql = "
                INSERT INTO tool_tol (
                    tool_name_tol,
                    tool_description_tol,
                    rental_fee_tol,
                    id_acc_tol,
                    id_tcd_tol
                    {$durationCol}
                    {$fuelCol}
                ) VALUES (
                    :name,
                    :description,
                    :fee,
                    :owner,
                    (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = :condition)
                    {$durationVal}
                    {$fuelVal}
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':name', $data['tool_name'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], $data['description'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fee', $data['rental_fee'], PDO::PARAM_STR);
            $stmt->bindValue(':owner', $data['owner_id'], PDO::PARAM_INT);
            $stmt->bindValue(':condition', $data['condition'], PDO::PARAM_STR);

            if ($hasDuration) {
                $stmt->bindValue(':duration', $data['loan_duration'], PDO::PARAM_INT);
            }

            if ($hasFuel) {
                $stmt->bindValue(':fuel_type', $data['fuel_type'], PDO::PARAM_STR);
            }

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
     *   1. UPDATE tool_tol — name, description, fee, condition, loan duration
     *   2. DELETE tool_category_tolcat — remove old category link
     *   3. INSERT tool_category_tolcat — assign new category
     *   4. (conditional) DELETE old + INSERT new tool_image_tim row
     *
     * @param  int   $toolId  Tool primary key
     * @param  array{tool_name: string, description: ?string, rental_fee: float,
     *               condition: string, loan_duration: ?int,
     *               category_id: int, image_filename: ?string} $data
     */
    public static function update(int $toolId, array $data): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $hasDuration = $data['loan_duration'] !== null;
        $hasFuel     = ($data['fuel_type'] ?? null) !== null;

        try {
            $durationSet = $hasDuration ? ', default_loan_duration_hours_tol = :duration' : '';
            $fuelSet     = $hasFuel
                ? ', id_ftp_tol = (SELECT id_ftp FROM fuel_type_ftp WHERE fuel_name_ftp = :fuel_type)'
                : ', id_ftp_tol = NULL';

            $stmt = $pdo->prepare("
                UPDATE tool_tol SET
                    tool_name_tol        = :name,
                    tool_description_tol = :description,
                    rental_fee_tol       = :fee,
                    id_tcd_tol           = (SELECT id_tcd FROM tool_condition_tcd WHERE condition_name_tcd = :condition)
                    {$durationSet}
                    {$fuelSet}
                WHERE id_tol = :id
            ");

            $stmt->bindValue(':name', $data['tool_name'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'], $data['description'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':fee', $data['rental_fee'], PDO::PARAM_STR);
            $stmt->bindValue(':condition', $data['condition'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $toolId, PDO::PARAM_INT);

            if ($hasDuration) {
                $stmt->bindValue(':duration', $data['loan_duration'], PDO::PARAM_INT);
            }

            if ($hasFuel) {
                $stmt->bindValue(':fuel_type', $data['fuel_type'], PDO::PARAM_STR);
            }

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
     * Flip a tool's listing flag between listed and unlisted.
     *
     * @param int $toolId Tool primary key
     */
    public static function toggleAvailability(int $toolId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE tool_tol
            SET is_available_tol = NOT is_available_tol
            WHERE id_tol = :id
        ");

        $stmt->bindValue(':id', $toolId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Fetch the primary category ID for a tool.
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
     * Fetch category name + icon for a batch of tool IDs.
     *
     * Returns both values from the same category row so they always
     * match. When $preferredCategoryId is set (browsing by category),
     * that category is selected over the alphabetic default.
     *
     * @param  int[] $toolIds              Tool primary keys
     * @param  ?int  $preferredCategoryId  Prefer this category when a tool belongs to multiple
     * @return array<int, array{category_name: string, category_icon: ?string}>
     */
    public static function getCategoryDataForTools(array $toolIds, ?int $preferredCategoryId = null): array
    {
        if ($toolIds === []) {
            return [];
        }

        $pdo = Database::connection();

        $placeholders = implode(',', array_fill(0, count($toolIds), '?'));

        $orderExpr = $preferredCategoryId !== null
            ? 'CASE WHEN c.id_cat = ? THEN 0 ELSE 1 END, c.category_name_cat'
            : 'c.category_name_cat';

        $sql = "
            SELECT sub.id_tol_tolcat, sub.category_name, sub.category_icon
            FROM (
                SELECT tc.id_tol_tolcat,
                       c.category_name_cat AS category_name,
                       vec.file_name_vec AS category_icon,
                       ROW_NUMBER() OVER (
                           PARTITION BY tc.id_tol_tolcat
                           ORDER BY {$orderExpr}
                       ) AS rn
                FROM tool_category_tolcat tc
                JOIN category_cat c ON tc.id_cat_tolcat = c.id_cat
                LEFT JOIN vector_image_vec vec ON c.id_vec_cat = vec.id_vec
                WHERE tc.id_tol_tolcat IN ({$placeholders})
            ) sub
            WHERE sub.rn = 1
        ";

        $stmt = $pdo->prepare($sql);

        $paramIndex = 1;

        if ($preferredCategoryId !== null) {
            $stmt->bindValue($paramIndex++, $preferredCategoryId, PDO::PARAM_INT);
        }

        foreach (array_values($toolIds) as $id) {
            $stmt->bindValue($paramIndex++, $id, PDO::PARAM_INT);
        }

        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['id_tol_tolcat']] = [
                'category_name' => $row['category_name'],
                'category_icon' => $row['category_icon'],
            ];
        }

        return $map;
    }

    /**
     * Search tools by name or owner for admin global search.
     *
     * @return array<int, array{id_tol: int, tool_name_tol: string, owner_name: string, tool_condition: string, rental_fee_tol: string}>
     */
    public static function adminSearch(string $term, int $limit = 5): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                id_tol,
                tool_name_tol,
                owner_name,
                tool_condition,
                rental_fee_tol
            FROM tool_detail_v
            WHERE tool_name_tol LIKE CONCAT('%', :term1, '%')
               OR owner_name LIKE CONCAT('%', :term2, '%')
            ORDER BY tool_name_tol ASC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':term1', $term);
        $stmt->bindValue(':term2', $term);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch all fuel types from the lookup table.
     *
     * @return array<string>  Fuel type names ordered by ID
     */
    public static function getFuelTypes(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT fuel_name_ftp
            FROM fuel_type_ftp
            ORDER BY id_ftp
        ");

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Fetch a paginated, sortable, filterable list of tools from tool_statistics_fast_v.
     *
     * @param  string  $sort          Pre-validated column name
     * @param  string  $dir           Pre-validated direction (ASC|DESC)
     * @param  ?string $condition     Filter by tool_condition value
     * @param  bool    $incidentsOnly Only tools with incident_count > 0
     * @param  ?string $search        LIKE on tool_name_tol and owner_name
     * @return array
     */
    public static function getAdminList(
        int $limit,
        int $offset,
        string $sort = 'created_at_tol',
        string $dir = 'DESC',
        ?string $condition = null,
        bool $incidentsOnly = false,
        ?string $search = null,
    ): array {
        $pdo = Database::connection();

        $where  = [];
        $params = [];

        if ($condition !== null) {
            $where[]              = 'tool_condition = :condition';
            $params[':condition'] = $condition;
        }

        if ($incidentsOnly) {
            $where[] = 'incident_count > 0';
        }

        if ($search !== null) {
            $where[]            = '(tool_name_tol LIKE CONCAT(\'%\', :search1, \'%\')
                                 OR owner_name LIKE CONCAT(\'%\', :search2, \'%\'))';
            $params[':search1'] = $search;
            $params[':search2'] = $search;
        }

        $whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT *
            FROM tool_statistics_fast_v
            $whereClause
            ORDER BY $sort $dir
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count tools matching the given filters for admin pagination.
     *
     * @return int
     */
    public static function getAdminFilteredCount(
        ?string $condition = null,
        bool $incidentsOnly = false,
        ?string $search = null,
    ): int {
        $pdo = Database::connection();

        $where  = [];
        $params = [];

        if ($condition !== null) {
            $where[]              = 'tool_condition = :condition';
            $params[':condition'] = $condition;
        }

        if ($incidentsOnly) {
            $where[] = 'incident_count > 0';
        }

        if ($search !== null) {
            $where[]            = '(tool_name_tol LIKE CONCAT(\'%\', :search1, \'%\')
                                 OR owner_name LIKE CONCAT(\'%\', :search2, \'%\'))';
            $params[':search1'] = $search;
            $params[':search2'] = $search;
        }

        $whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tool_statistics_fast_v $whereClause");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

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
                aim.file_name_aim AS owner_avatar,
                avv.file_name_avv AS owner_vector_avatar
            FROM tool_detail_v td
            LEFT JOIN account_image_aim aim
                ON aim.id_acc_aim = td.owner_id AND aim.is_primary_aim = 1
            LEFT JOIN account_acc acc_avv ON td.owner_id = acc_avv.id_acc
            LEFT JOIN avatar_vector_avv avv ON acc_avv.id_avv_acc = avv.id_avv
            WHERE td.id_tol = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $tool = $stmt->fetch();

        return $tool !== false ? $tool : null;
    }
}
