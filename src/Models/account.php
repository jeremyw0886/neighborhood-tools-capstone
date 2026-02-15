<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Account
{
    /**
     * Find a non-deleted account by email — used for login authentication.
     *
     * Joins role and primary avatar so the AuthController has everything
     * it needs to verify credentials and populate session variables.
     *
     * @return ?array{id_acc: int, first_name_acc: string, last_name_acc: string,
     *               email_address_acc: string, password_hash_acc: string,
     *               role_name_rol: string, account_status: string, avatar: ?string}
     */
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                a.id_acc,
                a.first_name_acc,
                a.last_name_acc,
                a.email_address_acc,
                a.password_hash_acc,
                r.role_name_rol,
                ast.status_name_ast AS account_status,
                aim.file_name_aim  AS avatar
            FROM active_account_v a
            JOIN role_rol r            ON a.id_rol_acc = r.id_rol
            JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
            LEFT JOIN account_image_aim aim
                ON a.id_acc = aim.id_acc_aim AND aim.is_primary_aim = TRUE
            WHERE a.email_address_acc = :email
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Find an account by ID using the full profile view.
     *
     * Returns the complete profile: name, role, status, neighborhood,
     * avatar, bio, tool count, and ratings.
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM account_profile_v
            WHERE id_acc = :id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Check whether a username is already taken.
     */
    public static function usernameExists(string $username): bool
    {
        $pdo = Database::connection();

        $sql = "SELECT 1 FROM account_acc WHERE username_acc = :username LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $username);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    /**
     * Create a new account and return the new ID.
     *
     * Uses subqueries and fn_get_account_status_id() to resolve FK values
     * by name — no hard-coded lookup IDs.
     *
     * @param array{first_name: string, last_name: string, username: string,
     *              email: string, password_hash: string, street_address: ?string,
     *              zip_code: string, neighborhood_id: ?int} $data
     */
    public static function create(array $data): int
    {
        $pdo = Database::connection();

        $sql = "
            INSERT INTO account_acc (
                first_name_acc,
                last_name_acc,
                username_acc,
                email_address_acc,
                password_hash_acc,
                street_address_acc,
                zip_code_acc,
                id_nbh_acc,
                id_rol_acc,
                id_ast_acc,
                id_cpr_acc
            ) VALUES (
                :first_name,
                :last_name,
                :username,
                :email,
                :password_hash,
                :street_address,
                :zip_code,
                :neighborhood_id,
                (SELECT id_rol FROM role_rol WHERE role_name_rol = 'member'),
                fn_get_account_status_id('active'),
                (SELECT id_cpr FROM contact_preference_cpr WHERE preference_name_cpr = 'email')
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':first_name', $data['first_name']);
        $stmt->bindValue(':last_name', $data['last_name']);
        $stmt->bindValue(':username', $data['username']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':password_hash', $data['password_hash']);
        $stmt->bindValue(':street_address', $data['street_address'], $data['street_address'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':zip_code', $data['zip_code']);
        $stmt->bindValue(':neighborhood_id', $data['neighborhood_id'], $data['neighborhood_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /**
     * Fetch reputation data for a user from the fast (materialized) view.
     *
     * Returns lender/borrower/overall ratings, tool counts, and completed borrows.
     *
     * @return ?array{id_acc: int, full_name: string, lender_avg_rating: float,
     *               lender_rating_count: int, borrower_avg_rating: float,
     *               borrower_rating_count: int, overall_avg_rating: float,
     *               total_rating_count: int, tools_owned: int, completed_borrows: int}
     */
    public static function getReputation(int $accountId): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT *
            FROM user_reputation_fast_v
            WHERE id_acc = :id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Fetch top-rated and most-active members for the home page.
     *
     * Joins account_profile_v (display data) with user_reputation_fast_v
     * (ranking metrics). Sorts by overall rating first, then by an activity
     * score (tools owned + completed borrows) as a tiebreaker/fallback.
     *
     * @param  int   $limit  Number of members to return (default 3)
     * @return array<int, array{id_acc: int, username: string, avatar: ?string,
     *               avg_rating: ?float, bio: ?string, tools_owned: int,
     *               completed_borrows: int}>
     */
    public static function getTopMembers(int $limit = 3): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                p.id_acc,
                p.username_acc               AS username,
                p.primary_image              AS avatar,
                r.overall_avg_rating         AS avg_rating,
                p.bio_text_abi               AS bio,
                r.tools_owned,
                r.completed_borrows,
                r.total_rating_count
            FROM account_profile_v p
            JOIN user_reputation_fast_v r ON p.id_acc = r.id_acc
            WHERE p.account_status = 'active'
              AND p.role_name_rol  = 'member'
            ORDER BY
                COALESCE(r.overall_avg_rating, 0) DESC,
                r.total_rating_count DESC,
                (r.tools_owned + r.completed_borrows) DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch all active members ranked by proximity to a city's downtown.
     *
     * Uses the "Downtown {City}" neighborhood point as reference, then ranks
     * every active member by ST_Distance_Sphere from that point. Resolves the
     * member's neighborhood name via id_nbh_acc first, falling back to the
     * primary ZIP-based neighborhood when the direct assignment is NULL.
     *
     * @param  string $city   City name (e.g. "Asheville", "Hendersonville")
     * @param  int    $limit  Max members to return (default 10)
     * @return array<int, array{id_acc: int, username: string, avatar: ?string,
     *               avg_rating: float, total_rating_count: int,
     *               neighborhood: ?string, is_top_member: int,
     *               distance_miles: float}>
     */
    public static function getNearbyMembers(string $city, int $limit = 10): array
    {
        $pdo = Database::connection();
        $downtown = 'Downtown ' . $city;

        $sql = "
            WITH city_ref AS (
                SELECT location_point_nbh AS ref_point
                FROM neighborhood_nbh
                WHERE neighborhood_name_nbh = :downtown
                LIMIT 1
            )
            SELECT
                p.id_acc,
                p.username_acc                    AS username,
                p.primary_image                   AS avatar,
                COALESCE(r.overall_avg_rating, 0) AS avg_rating,
                COALESCE(r.total_rating_count, 0) AS total_rating_count,
                COALESCE(
                    p.neighborhood_name_nbh,
                    nbh_z.neighborhood_name_nbh
                )                                 AS neighborhood,
                CASE
                    WHEN COALESCE(r.overall_avg_rating, 0) >= 4.0
                     AND COALESCE(r.total_rating_count, 0) >= 1
                    THEN 1 ELSE 0
                END                               AS is_top_member,
                ROUND(
                    ST_Distance_Sphere(
                        zpc.location_point_zpc,
                        city_ref.ref_point
                    ) / 1609.344,
                1)                                AS distance_miles
            FROM account_profile_v p
            CROSS JOIN city_ref
            LEFT JOIN user_reputation_fast_v r     ON p.id_acc = r.id_acc
            JOIN zip_code_zpc zpc                  ON p.zip_code_acc = zpc.zip_code_zpc
            LEFT JOIN neighborhood_zip_nbhzpc nz
                ON p.zip_code_acc = nz.zip_code_nbhzpc AND nz.is_primary_nbhzpc = TRUE
            LEFT JOIN neighborhood_nbh nbh_z       ON nz.id_nbh_nbhzpc = nbh_z.id_nbh
            WHERE p.account_status = 'active'
              AND p.role_name_rol  = 'member'
            HAVING distance_miles IS NOT NULL
            ORDER BY distance_miles ASC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':downtown', $downtown);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update the password hash for an account.
     */
    public static function updatePassword(int $accountId, string $passwordHash): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE account_acc
            SET password_hash_acc = :hash
            WHERE id_acc = :id
        ");

        $stmt->bindValue(':hash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Verify a plaintext password against a stored hash.
     */
    public static function verifyPassword(string $input, string $hash): bool
    {
        return password_verify(password: $input, hash: $hash);
    }
}
