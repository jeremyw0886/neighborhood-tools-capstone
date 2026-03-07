<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Rating
{
    /**
     * Submit a user-to-user rating via sp_rate_user().
     *
     * The SP validates: score 1–5, role is valid, borrow exists in
     * "returned" status, rater/target are participants, no self-rating.
     * Trigger trg_user_rating_before_insert provides defense-in-depth.
     *
     * @param  int     $borrowId   Completed borrow transaction ID
     * @param  int     $raterId    Account giving the rating
     * @param  int     $targetId   Account being rated
     * @param  string  $role       'lender' or 'borrower'
     * @param  int     $score      Rating 1–5
     * @param  ?string $reviewText Optional review comment
     * @return array{rating_id: ?int, error: ?string}
     */
    public static function rateUser(
        int $borrowId,
        int $raterId,
        int $targetId,
        string $role,
        int $score,
        ?string $reviewText = null,
    ): array {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_rate_user(:borrow_id, :rater_id, :target_id, :role, :score, :review, @rating_id, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':rater_id', $raterId, PDO::PARAM_INT);
        $stmt->bindValue(':target_id', $targetId, PDO::PARAM_INT);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':score', $score, PDO::PARAM_INT);
        $stmt->bindValue(':review', $reviewText, $reviewText === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @rating_id AS rating_id, @error_msg AS error')->fetch();

        return [
            'rating_id' => $out['rating_id'] !== null ? (int) $out['rating_id'] : null,
            'error'     => $out['error'],
        ];
    }

    /**
     * Submit a tool rating via sp_rate_tool().
     *
     * The SP validates: score 1–5, borrow exists in "returned" status,
     * rater is the borrower (not the lender). Trigger trg_tool_rating_before_insert
     * enforces the same rules as defense-in-depth.
     *
     * @param  int     $borrowId   Completed borrow transaction ID
     * @param  int     $raterId    Account giving the rating (must be borrower)
     * @param  int     $score      Rating 1–5
     * @param  ?string $reviewText Optional review comment
     * @return array{rating_id: ?int, error: ?string}
     */
    public static function rateTool(
        int $borrowId,
        int $raterId,
        int $score,
        ?string $reviewText = null,
    ): array {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_rate_tool(:borrow_id, :rater_id, :score, :review, @rating_id, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':rater_id', $raterId, PDO::PARAM_INT);
        $stmt->bindValue(':score', $score, PDO::PARAM_INT);
        $stmt->bindValue(':review', $reviewText, $reviewText === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @rating_id AS rating_id, @error_msg AS error')->fetch();

        return [
            'rating_id' => $out['rating_id'] !== null ? (int) $out['rating_id'] : null,
            'error'     => $out['error'],
        ];
    }

    /**
     * Check whether a user has already submitted a user rating for a borrow.
     *
     * Uses the unique constraint (id_bor_urt, id_acc_urt, id_rtr_urt) —
     * a row existing means the rating was already submitted.
     */
    public static function hasUserRated(int $borrowId, int $raterId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            SELECT 1
            FROM user_rating_urt
            WHERE id_bor_urt = :borrow_id
              AND id_acc_urt = :rater_id
            LIMIT 1
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':rater_id', $raterId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    /**
     * Return the set of borrow IDs (from a given list) that a user has already rated.
     *
     * @param  int[] $borrowIds Borrow IDs to check
     * @param  int   $raterId   Account that would have submitted the rating
     * @return int[]            Subset of $borrowIds that have been rated
     */
    public static function getRatedBorrowIds(array $borrowIds, int $raterId): array
    {
        if ($borrowIds === []) {
            return [];
        }

        $pdo = Database::connection();

        $placeholders = implode(',', array_fill(0, count($borrowIds), '?'));

        $stmt = $pdo->prepare("
            SELECT DISTINCT id_bor_urt
            FROM user_rating_urt
            WHERE id_bor_urt IN ({$placeholders})
              AND id_acc_urt = ?
        ");

        $i = 1;
        foreach ($borrowIds as $id) {
            $stmt->bindValue($i++, $id, PDO::PARAM_INT);
        }
        $stmt->bindValue($i, $raterId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Fetch all user ratings submitted for a borrow.
     *
     * @return array<int, array{rater_name: string, score: int, review: ?string, role: string, created_at: string}>
     */
    public static function getUserRatingsForBorrow(int $borrowId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS rater_name,
                urt.id_acc_urt AS rater_id,
                urt.score_urt AS score,
                urt.comment_text_urt AS review,
                rtr.role_name_rtr AS role,
                urt.created_at_urt AS created_at
            FROM user_rating_urt urt
            JOIN account_acc a ON urt.id_acc_urt = a.id_acc
            JOIN rating_role_rtr rtr ON urt.id_rtr_urt = rtr.id_rtr
            WHERE urt.id_bor_urt = :borrow_id
            ORDER BY urt.created_at_urt ASC
        ");

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch the tool rating submitted for a borrow.
     *
     * @return ?array{rater_name: string, score: int, review: ?string, tool_name: string, created_at: string}
     */
    public static function getToolRatingForBorrow(int $borrowId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS rater_name,
                trt.score_trt AS score,
                trt.comment_text_trt AS review,
                t.tool_name_tol AS tool_name,
                trt.created_at_trt AS created_at
            FROM tool_rating_trt trt
            JOIN account_acc a ON trt.id_acc_trt = a.id_acc
            JOIN tool_tol t ON trt.id_tol_trt = t.id_tol
            WHERE trt.id_bor_trt = :borrow_id
            LIMIT 1
        ");

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Check whether a user has already submitted a tool rating for a borrow.
     *
     * Uses the unique constraint (id_bor_trt, id_tol_trt).
     */
    public static function hasToolRated(int $borrowId, int $raterId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('
            SELECT 1
            FROM tool_rating_trt
            WHERE id_bor_trt = :borrow_id
              AND id_acc_trt = :rater_id
            LIMIT 1
        ');

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->bindValue(':rater_id', $raterId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }
}
