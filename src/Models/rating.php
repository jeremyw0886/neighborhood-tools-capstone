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
