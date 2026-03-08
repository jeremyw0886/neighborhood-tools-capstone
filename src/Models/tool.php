<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Tool
{
    private const int PRIOR_WEIGHT = 3;
    private const string PRIOR_MEAN = '3.5';
    private const int MAX_PER_OWNER = 2;
    private const int NEW_ARRIVAL_DAYS = 30;

    /**
     * Fetch popular/featured tools with reserved slots for new arrivals.
     *
     * @param  int   $limit     Total tools to return
     * @param  int   $newSlots  Slots reserved for unrated new arrivals
     * @return array
     */
    public static function getFeatured(int $limit = 6, int $newSlots = 2): array
    {
        $newSlots = max(0, min($newSlots, $limit - 1));

        $ranked = self::getRankedTools($limit);
        $rankedIds = array_column($ranked, 'id_tol');

        $newArrivals = self::getRecentUnratedTools($newSlots, $rankedIds);

        $rankedSlots = $limit - count($newArrivals);
        $ranked = array_slice($ranked, 0, $rankedSlots);

        foreach ($ranked as &$row) {
            $row['is_new_arrival'] = false;
        }
        unset($row);

        foreach ($newArrivals as &$row) {
            $row['is_new_arrival'] = true;
        }
        unset($row);

        $results = array_merge($ranked, $newArrivals);

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
     * Rank available tools by Bayesian-weighted rating blended with borrow demand.
     *
     * @param  int   $limit  Maximum rows to return
     * @return array
     */
    private static function getRankedTools(int $limit): array
    {
        $pdo = Database::connection();

        $borrowedId = $pdo->query(
            "SELECT fn_get_borrow_status_id('borrowed')"
        )->fetchColumn();
        $returnedId = $pdo->query(
            "SELECT fn_get_borrow_status_id('returned')"
        )->fetchColumn();

        $pw = self::PRIOR_WEIGHT;
        $pm = self::PRIOR_MEAN;
        $maxPerOwner = self::MAX_PER_OWNER;

        $sql = "
            WITH scored AS (
                SELECT
                    av.id_tol,
                    av.owner_id,
                    av.tool_name_tol,
                    av.rental_fee_tol,
                    av.tool_condition,
                    av.is_deposit_required_tol,
                    av.default_deposit_amount_tol,
                    av.primary_image,
                    COALESCE(rs.avg_rating, 0) AS avg_rating,
                    av.owner_name,
                    aim.file_name_aim AS owner_avatar,
                    avv.file_name_avv AS owner_vector_avatar,
                    av.created_at_tol,
                    COALESCE(rs.rating_count, 0) AS rating_count,
                    COALESCE(bc.borrow_count, 0) AS borrow_count,
                    (
                        COALESCE(rs.rating_count, 0) * COALESCE(rs.avg_rating, 0)
                        + {$pw} * {$pm}
                    ) / (
                        COALESCE(rs.rating_count, 0) + {$pw}
                    ) AS weighted_rating
                FROM available_tool_v av
                LEFT JOIN (
                    SELECT id_tol_trt,
                           ROUND(AVG(score_trt), 1) AS avg_rating,
                           COUNT(*) AS rating_count
                    FROM tool_rating_trt
                    GROUP BY id_tol_trt
                ) rs ON av.id_tol = rs.id_tol_trt
                LEFT JOIN (
                    SELECT id_tol_bor,
                           COUNT(*) AS borrow_count
                    FROM borrow_bor
                    WHERE id_bst_bor IN (:bs_borrowed_id, :bs_returned_id)
                    GROUP BY id_tol_bor
                ) bc ON av.id_tol = bc.id_tol_bor
                LEFT JOIN account_image_aim aim
                    ON aim.id_acc_aim = av.owner_id AND aim.is_primary_aim = 1
                LEFT JOIN account_acc acc_avv ON av.owner_id = acc_avv.id_acc
                LEFT JOIN avatar_vector_avv avv ON acc_avv.id_avv_acc = avv.id_avv
            ),
            popularity AS (
                SELECT *,
                    (weighted_rating * 0.6)
                    + (LEAST(borrow_count, 20) / 20.0 * 5 * 0.4) AS popularity_score
                FROM scored
            ),
            diversity AS (
                SELECT *,
                    ROW_NUMBER() OVER (
                        PARTITION BY owner_id
                        ORDER BY popularity_score DESC
                    ) AS owner_rank
                FROM popularity
            )
            SELECT id_tol, owner_id, tool_name_tol, rental_fee_tol,
                   tool_condition, is_deposit_required_tol,
                   default_deposit_amount_tol, primary_image,
                   avg_rating, rating_count, owner_name,
                   owner_avatar, owner_vector_avatar
            FROM diversity
            WHERE owner_rank <= {$maxPerOwner}
            ORDER BY popularity_score DESC, created_at_tol DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':bs_borrowed_id', $borrowedId, PDO::PARAM_INT);
        $stmt->bindValue(':bs_returned_id', $returnedId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll();
        $stmt->closeCursor();

        return $results;
    }

    /**
     * Fetch recent unrated tools for new-arrival slots.
     *
     * @param  int   $limit       Max rows to return
     * @param  int[] $excludeIds  Tool IDs already in the ranked set
     * @return array
     */
    private static function getRecentUnratedTools(int $limit, array $excludeIds): array
    {
        if ($limit < 1) {
            return [];
        }

        $pdo = Database::connection();
        $days = self::NEW_ARRIVAL_DAYS;

        $excludeClause = $excludeIds !== []
            ? 'AND av.id_tol NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')'
            : '';

        $sql = "
            WITH unrated AS (
                SELECT
                    av.id_tol,
                    av.owner_id,
                    av.tool_name_tol,
                    av.rental_fee_tol,
                    av.tool_condition,
                    av.is_deposit_required_tol,
                    av.default_deposit_amount_tol,
                    av.primary_image,
                    0 AS avg_rating,
                    0 AS rating_count,
                    av.owner_name,
                    aim.file_name_aim AS owner_avatar,
                    avv.file_name_avv AS owner_vector_avatar,
                    av.created_at_tol,
                    ROW_NUMBER() OVER (
                        PARTITION BY av.owner_id
                        ORDER BY av.created_at_tol DESC
                    ) AS owner_rank
                FROM available_tool_v av
                LEFT JOIN (
                    SELECT id_tol_trt
                    FROM tool_rating_trt
                    GROUP BY id_tol_trt
                ) rs ON av.id_tol = rs.id_tol_trt
                LEFT JOIN account_image_aim aim
                    ON aim.id_acc_aim = av.owner_id AND aim.is_primary_aim = 1
                LEFT JOIN account_acc acc_avv ON av.owner_id = acc_avv.id_acc
                LEFT JOIN avatar_vector_avv avv ON acc_avv.id_avv_acc = avv.id_avv
                WHERE rs.id_tol_trt IS NULL
                  AND av.created_at_tol >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                  {$excludeClause}
            )
            SELECT id_tol, owner_id, tool_name_tol, rental_fee_tol,
                   tool_condition, is_deposit_required_tol,
                   default_deposit_amount_tol, primary_image,
                   avg_rating, rating_count, owner_name,
                   owner_avatar, owner_vector_avatar
            FROM unrated
            WHERE owner_rank <= 1
            ORDER BY created_at_tol DESC
            LIMIT ?
        ";

        $stmt = $pdo->prepare($sql);

        $paramIndex = 1;
        foreach ($excludeIds as $id) {
            $stmt->bindValue($paramIndex++, $id, PDO::PARAM_INT);
        }
        $stmt->bindValue($paramIndex, $limit, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll();
        $stmt->closeCursor();

        return $results;
    }

    /** Meters per mile for ST_Distance_Sphere conversion. */
    private const float METERS_PER_MILE = 1609.344;

    /**
     * Search available tools with optional spatial or exact-ZIP filtering.
     *
     * @param  string  $term            FULLTEXT search against name + description
     * @param  ?int    $categoryId      Filter by category, or null for all
     * @param  ?string $zip             Filter by owner zip code, or null for all
     * @param  ?float  $maxFee          Maximum rental fee, or null for no cap
     * @param  int     $limit           Results per page
     * @param  int     $offset          Pagination offset
     * @param  ?int    $radius          Search radius in miles, or null for exact ZIP
     * @param  ?int    $excludeOwnerId  Omit tools owned by this account
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
        ?int $excludeOwnerId = null,
        bool $excludeLentOut = false,
    ): array {
        if ($radius !== null && $zip !== null) {
            return self::searchByDistance($term, $categoryId, $zip, $maxFee, $limit, $offset, $radius, $excludeOwnerId, $excludeLentOut);
        }

        return self::searchStandard($term, $categoryId, $zip, $maxFee, $limit, $offset, $excludeOwnerId, $excludeLentOut);
    }

    /**
     * Non-spatial search with all filters applied in SQL.
     *
     * @return array
     */
    private static function searchStandard(
        string $term,
        ?int $categoryId,
        ?string $zip,
        ?float $maxFee,
        int $limit,
        int $offset,
        ?int $excludeOwnerId = null,
        bool $excludeLentOut = false,
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
                t.created_at_tol,
                (SELECT COALESCE(AVG(trt.score_trt), 0)
                   FROM tool_rating_trt trt
                  WHERE trt.id_tol_trt = t.id_tol) AS avg_rating,
                (SELECT COUNT(*)
                   FROM tool_rating_trt trt
                  WHERE trt.id_tol_trt = t.id_tol) AS rating_count,
                EXISTS (
                    SELECT 1 FROM borrow_bor b
                     WHERE b.id_tol_bor = t.id_tol
                       AND b.id_bst_bor = fn_get_borrow_status_id(:bs_borrowed)
                ) AS is_lent_out,
                (SELECT MAX(b.created_at_bor)
                   FROM borrow_bor b
                  WHERE b.id_tol_bor = t.id_tol) AS last_activity
        ";

        $joins = "
            FROM tool_tol t
            JOIN account_acc a ON t.id_acc_tol = a.id_acc
            JOIN tool_condition_tcd tcd ON t.id_tcd_tol = tcd.id_tcd
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
            WHERE t.is_available_tol = TRUE
              AND a.id_ast_acc != fn_get_account_status_id(:deleted_status)
              AND NOT EXISTS (
                  SELECT 1 FROM availability_block_avb avb
                   WHERE avb.id_tol_avb = t.id_tol
                     AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
              )
        ";

        if ($searchTerm !== null) {
            $where .= " AND MATCH(t.tool_name_tol, t.tool_description_tol) AGAINST(:term IN NATURAL LANGUAGE MODE)";
        }

        if ($zip !== null) {
            $where .= " AND a.zip_code_acc = :zip";
        }

        if ($categoryId !== null) {
            $where .= " AND tc.id_cat_tolcat = :category";
        }

        if ($maxFee !== null) {
            $where .= " AND t.rental_fee_tol <= :maxFee";
        }

        if ($excludeOwnerId !== null) {
            $where .= " AND t.id_acc_tol != :exclude_owner";
        }

        if ($excludeLentOut) {
            $where .= " AND NOT EXISTS (
                SELECT 1 FROM borrow_bor bl
                 WHERE bl.id_tol_bor = t.id_tol
                   AND bl.id_bst_bor = fn_get_borrow_status_id(:bs_lent_filter)
            )";
        }

        $sql = $select . $joins . $where
             . " ORDER BY is_lent_out ASC, COALESCE(last_activity, t.created_at_tol) DESC, t.id_tol ASC"
             . " LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':deleted_status', 'deleted');
        $stmt->bindValue(':bs_borrowed', 'borrowed');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($searchTerm !== null) {
            $stmt->bindValue(':term', $searchTerm);
        }

        if ($zip !== null) {
            $stmt->bindValue(':zip', $zip);
        }

        if ($categoryId !== null) {
            $stmt->bindValue(':category', $categoryId, PDO::PARAM_INT);
        }

        if ($excludeOwnerId !== null) {
            $stmt->bindValue(':exclude_owner', $excludeOwnerId, PDO::PARAM_INT);
        }

        if ($maxFee !== null) {
            $stmt->bindValue(':maxFee', $maxFee);
        }

        if ($excludeLentOut) {
            $stmt->bindValue(':bs_lent_filter', 'borrowed');
        }

        $stmt->execute();

        $results = $stmt->fetchAll();
        $cutoff  = strtotime('-' . self::NEW_ARRIVAL_DAYS . ' days');

        foreach ($results as &$row) {
            $row['is_new_arrival'] = isset($row['created_at_tol'])
                && strtotime($row['created_at_tol']) >= $cutoff
                && (int) ($row['rating_count'] ?? 1) === 0;
        }
        unset($row);

        return $results;
    }

    /**
     * Spatial search returning tools within $radius miles of $originZip.
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
        ?int $excludeOwnerId = null,
        bool $excludeLentOut = false,
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
                t.created_at_tol,
                (SELECT COALESCE(AVG(trt.score_trt), 0)
                   FROM tool_rating_trt trt
                  WHERE trt.id_tol_trt = t.id_tol) AS avg_rating,
                (SELECT COUNT(*)
                   FROM tool_rating_trt trt
                  WHERE trt.id_tol_trt = t.id_tol) AS rating_count,
                EXISTS (
                    SELECT 1 FROM borrow_bor b
                     WHERE b.id_tol_bor = t.id_tol
                       AND b.id_bst_bor = fn_get_borrow_status_id(:bs_borrowed)
                ) AS is_lent_out,
                (SELECT MAX(b.created_at_bor)
                   FROM borrow_bor b
                  WHERE b.id_tol_bor = t.id_tol) AS last_activity,
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

        if ($excludeOwnerId !== null) {
            $where .= " AND t.id_acc_tol != :exclude_owner";
        }

        if ($excludeLentOut) {
            $where .= " AND NOT EXISTS (
                SELECT 1 FROM borrow_bor bl
                 WHERE bl.id_tol_bor = t.id_tol
                   AND bl.id_bst_bor = fn_get_borrow_status_id(:bs_lent_filter)
            )";
        }

        $where .= " AND ST_Distance_Sphere(z.location_point_zpc, origin.location_point_zpc) / :mpm_filter <= :radius";

        $sql = $select . $joins . $where
             . " ORDER BY is_lent_out ASC, COALESCE(last_activity, t.created_at_tol) DESC, distance_miles ASC, t.id_tol ASC"
             . " LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':mpm_select', self::METERS_PER_MILE);
        $stmt->bindValue(':mpm_filter', self::METERS_PER_MILE);
        $stmt->bindValue(':origin_zip', $originZip);
        $stmt->bindValue(':deleted_status', 'deleted');
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

        if ($excludeOwnerId !== null) {
            $stmt->bindValue(':exclude_owner', $excludeOwnerId, PDO::PARAM_INT);
        }

        if ($maxFee !== null) {
            $stmt->bindValue(':maxFee', $maxFee);
        }

        if ($excludeLentOut) {
            $stmt->bindValue(':bs_lent_filter', 'borrowed');
        }

        $stmt->execute();

        $results = $stmt->fetchAll();
        $cutoff  = strtotime('-' . self::NEW_ARRIVAL_DAYS . ' days');

        foreach ($results as &$row) {
            $row['is_new_arrival'] = isset($row['created_at_tol'])
                && strtotime($row['created_at_tol']) >= $cutoff
                && (int) ($row['rating_count'] ?? 1) === 0;
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
        ?int $excludeOwnerId = null,
        bool $excludeLentOut = false,
    ): int {
        $pdo = Database::connection();
        $useDistance = $radius !== null && $zip !== null;

        $where = [
            't.is_available_tol = TRUE',
            'a.id_ast_acc != fn_get_account_status_id(:deleted_status)',
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
            $where[] = 'ST_Distance_Sphere(z.location_point_zpc, origin.location_point_zpc) / :meters_per_mile <= :radius';
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

        if ($excludeOwnerId !== null) {
            $where[] = 't.id_acc_tol != :exclude_owner';
        }

        if ($excludeLentOut) {
            $where[] = 'NOT EXISTS (
                SELECT 1 FROM borrow_bor bl
                 WHERE bl.id_tol_bor = t.id_tol
                   AND bl.id_bst_bor = fn_get_borrow_status_id(:bs_lent_filter)
            )';
        }

        $sql = 'SELECT COUNT(DISTINCT t.id_tol) '
             . 'FROM tool_tol t '
             . implode(' ', $joins) . ' '
             . 'WHERE ' . implode(' AND ', $where);

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':deleted_status', 'deleted', PDO::PARAM_STR);

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

        if ($excludeOwnerId !== null) {
            $stmt->bindValue(':exclude_owner', $excludeOwnerId, PDO::PARAM_INT);
        }

        if ($categoryId !== null) {
            $stmt->bindValue(':category', $categoryId, PDO::PARAM_INT);
        }

        if ($maxFee !== null) {
            $stmt->bindValue(':maxFee', $maxFee, PDO::PARAM_STR);
        }

        if ($excludeLentOut) {
            $stmt->bindValue(':bs_lent_filter', 'borrowed', PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Live per-category tool counts matching browse-page filtering.
     *
     * @param  ?int $excludeOwnerId  Omit tools owned by this account
     * @return array<int, int>       [category_id => tool_count]
     */
    public static function getBrowseableCountsByCategory(?int $excludeOwnerId = null): array
    {
        $pdo = Database::connection();

        $where = [
            't.is_available_tol = TRUE',
            'a.id_ast_acc != fn_get_account_status_id(:deleted_status)',
            'NOT EXISTS (
                SELECT 1 FROM availability_block_avb avb
                WHERE avb.id_tol_avb = t.id_tol
                  AND NOW() BETWEEN avb.start_at_avb AND avb.end_at_avb
            )',
        ];

        if ($excludeOwnerId !== null) {
            $where[] = 't.id_acc_tol != :exclude_owner';
        }

        $sql = 'SELECT tc.id_cat_tolcat AS category_id, COUNT(DISTINCT t.id_tol) AS tool_count '
             . 'FROM tool_tol t '
             . 'JOIN account_acc a ON t.id_acc_tol = a.id_acc '
             . 'JOIN tool_category_tolcat tc ON t.id_tol = tc.id_tol_tolcat '
             . 'WHERE ' . implode(' AND ', $where) . ' '
             . 'GROUP BY tc.id_cat_tolcat';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':deleted_status', 'deleted', PDO::PARAM_STR);

        if ($excludeOwnerId !== null) {
            $stmt->bindValue(':exclude_owner', $excludeOwnerId, PDO::PARAM_INT);
        }

        $stmt->execute();

        $counts = [];

        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['category_id']] = (int) $row['tool_count'];
        }

        return $counts;
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
     * Create a new tool listing with category assignment and optional images.
     *
     * @param  array{tool_name: string, description: ?string, rental_fee: float,
     *               owner_id: int, category_id: int, condition: string,
     *               loan_duration: ?int,
     *               image_filenames: array<array{filename: string, alt_text: ?string}>} $data
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

            $stmt = $pdo->prepare("
                INSERT INTO tool_category_tolcat (id_tol_tolcat, id_cat_tolcat)
                VALUES (:tool, :category)
            ");
            $stmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
            $stmt->bindValue(':category', $data['category_id'], PDO::PARAM_INT);
            $stmt->execute();

            $images = $data['image_filenames'] ?? [];

            if ($images !== []) {
                $imgStmt = $pdo->prepare("
                    INSERT INTO tool_image_tim
                        (id_tol_tim, file_name_tim, alt_text_tim, is_primary_tim, sort_order_tim)
                    VALUES (:tool, :filename, :altText, :isPrimary, :sortOrder)
                ");

                foreach ($images as $i => $img) {
                    $altText = $img['alt_text'] ?? null;

                    $imgStmt->bindValue(':tool', $toolId, PDO::PARAM_INT);
                    $imgStmt->bindValue(':filename', $img['filename'], PDO::PARAM_STR);
                    $imgStmt->bindValue(':altText', $altText, $altText !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                    $imgStmt->bindValue(':isPrimary', $i === 0, PDO::PARAM_BOOL);
                    $imgStmt->bindValue(':sortOrder', $i + 1, PDO::PARAM_INT);
                    $imgStmt->execute();
                }
            }

            $pdo->commit();

            return $toolId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing tool listing and its category.
     *
     * Image management is handled by dedicated endpoints (addImage, deleteImage, etc.).
     *
     * @param  int   $toolId  Tool primary key
     * @param  array{tool_name: string, description: ?string, rental_fee: float,
     *               condition: string, loan_duration: ?int, category_id: int} $data
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
     * Each tool belongs to exactly one category via the junction table.
     *
     * @param  int[] $toolIds  Tool primary keys
     * @return array<int, array{category_name: string, category_icon: ?string}>
     */
    public static function getCategoryDataForTools(array $toolIds): array
    {
        if ($toolIds === []) {
            return [];
        }

        $pdo = Database::connection();

        $placeholders = implode(',', array_fill(0, count($toolIds), '?'));

        $sql = "
            SELECT tc.id_tol_tolcat,
                   c.category_name_cat AS category_name,
                   vec.file_name_vec AS category_icon
            FROM tool_category_tolcat tc
            JOIN category_cat c ON tc.id_cat_tolcat = c.id_cat
            LEFT JOIN vector_image_vec vec ON c.id_vec_cat = vec.id_vec
            WHERE tc.id_tol_tolcat IN ({$placeholders})
        ";

        $stmt = $pdo->prepare($sql);

        foreach (array_values($toolIds) as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
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

    /**
     * Fetch all images for a tool, ordered for gallery display.
     *
     * @param  int   $toolId  Tool primary key
     * @return array
     */
    public static function getImages(int $toolId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT id_tim, id_tol_tim, file_name_tim, alt_text_tim,
                   is_primary_tim, sort_order_tim, focal_x_tim, focal_y_tim,
                   uploaded_at_tim
            FROM tool_image_tim
            WHERE id_tol_tim = :toolId
            ORDER BY sort_order_tim ASC, id_tim ASC
        ");

        $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Add an image to a tool listing.
     *
     * @param  int     $toolId    Tool primary key
     * @param  string  $filename  Stored filename
     * @param  ?string $altText   Alt text for the image
     * @param  bool    $isPrimary Whether this is the primary image
     * @param  int     $sortOrder Display order position
     * @return int     The new image row's primary key (id_tim)
     */
    public static function addImage(
        int $toolId,
        string $filename,
        ?string $altText,
        bool $isPrimary,
        int $sortOrder,
        int $focalX = 50,
        int $focalY = 50,
        ?int $width = null,
    ): int {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            if ($isPrimary) {
                $stmt = $pdo->prepare("
                    UPDATE tool_image_tim
                    SET is_primary_tim = FALSE
                    WHERE id_tol_tim = :toolId AND is_primary_tim = TRUE
                ");
                $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
                $stmt->execute();
            }

            $stmt = $pdo->prepare("
                INSERT INTO tool_image_tim
                    (id_tol_tim, file_name_tim, alt_text_tim, is_primary_tim, sort_order_tim, focal_x_tim, focal_y_tim, width_tim)
                VALUES (:toolId, :filename, :altText, :isPrimary, :sortOrder, :focalX, :focalY, :width)
            ");
            $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
            $stmt->bindValue(':filename', $filename, PDO::PARAM_STR);
            $stmt->bindValue(':altText', $altText, $altText !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':isPrimary', $isPrimary, PDO::PARAM_BOOL);
            $stmt->bindValue(':sortOrder', $sortOrder, PDO::PARAM_INT);
            $stmt->bindValue(':focalX', $focalX, PDO::PARAM_INT);
            $stmt->bindValue(':focalY', $focalY, PDO::PARAM_INT);
            $stmt->bindValue(':width', $width, $width !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();

            $imageId = (int) $pdo->lastInsertId();

            $pdo->commit();

            return $imageId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a tool image and promote the next image to primary if needed.
     *
     * @param  int     $imageId  Image primary key (id_tim)
     * @return ?string Filename of the deleted image for disk cleanup, or null if not found
     */
    public static function deleteImage(int $imageId): ?string
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT id_tim, id_tol_tim, file_name_tim, is_primary_tim
            FROM tool_image_tim
            WHERE id_tim = :id
        ");
        $stmt->bindValue(':id', $imageId, PDO::PARAM_INT);
        $stmt->execute();

        $image = $stmt->fetch();

        if ($image === false) {
            return null;
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("DELETE FROM tool_image_tim WHERE id_tim = :id");
            $stmt->bindValue(':id', $imageId, PDO::PARAM_INT);
            $stmt->execute();

            if ($image['is_primary_tim']) {
                $stmt = $pdo->prepare("
                    UPDATE tool_image_tim
                    SET is_primary_tim = TRUE
                    WHERE id_tol_tim = :toolId
                    ORDER BY sort_order_tim ASC, id_tim ASC
                    LIMIT 1
                ");
                $stmt->bindValue(':toolId', $image['id_tol_tim'], PDO::PARAM_INT);
                $stmt->execute();
            }

            $pdo->commit();

            return $image['file_name_tim'];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reorder images for a tool by updating sort_order values.
     *
     * @param  int   $toolId     Tool primary key
     * @param  int[] $orderedIds Ordered array of image IDs (position = sort order)
     */
    public static function reorderImages(int $toolId, array $orderedIds): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE tool_image_tim
                SET sort_order_tim = :sortOrder
                WHERE id_tim = :imageId AND id_tol_tim = :toolId
            ");

            foreach ($orderedIds as $position => $imageId) {
                $stmt->bindValue(':sortOrder', $position + 1, PDO::PARAM_INT);
                $stmt->bindValue(':imageId', $imageId, PDO::PARAM_INT);
                $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
                $stmt->execute();
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Set a specific image as the primary for a tool.
     *
     * @param  int $toolId   Tool primary key
     * @param  int $imageId  Image primary key (id_tim)
     */
    public static function setPrimaryImage(int $toolId, int $imageId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE tool_image_tim
                SET is_primary_tim = FALSE
                WHERE id_tol_tim = :toolId AND is_primary_tim = TRUE
            ");
            $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("
                UPDATE tool_image_tim
                SET is_primary_tim = TRUE
                WHERE id_tim = :imageId AND id_tol_tim = :toolId
            ");
            $stmt->bindValue(':imageId', $imageId, PDO::PARAM_INT);
            $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update the alt text for a tool image.
     *
     * @param  int    $imageId  Image primary key (id_tim)
     * @param  string $altText  New alt text value
     */
    public static function updateImageAltText(int $imageId, string $altText): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE tool_image_tim
            SET alt_text_tim = :altText
            WHERE id_tim = :id
        ");
        $stmt->bindValue(':altText', $altText, PDO::PARAM_STR);
        $stmt->bindValue(':id', $imageId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Update the focal point for a tool image.
     *
     * @param int $imageId Image primary key (id_tim)
     * @param int $focalX  Horizontal focal point 0-100
     * @param int $focalY  Vertical focal point 0-100
     */
    public static function updateFocalPoint(int $imageId, int $focalX, int $focalY): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE tool_image_tim
            SET focal_x_tim = :focalX, focal_y_tim = :focalY
            WHERE id_tim = :id
        ");
        $stmt->bindValue(':focalX', max(0, min(100, $focalX)), PDO::PARAM_INT);
        $stmt->bindValue(':focalY', max(0, min(100, $focalY)), PDO::PARAM_INT);
        $stmt->bindValue(':id', $imageId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Count images attached to a tool (for enforcing the 6-image cap).
     *
     * @param  int $toolId  Tool primary key
     * @return int
     */
    public static function getImageCount(int $toolId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM tool_image_tim
            WHERE id_tol_tim = :toolId
        ");
        $stmt->bindValue(':toolId', $toolId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
