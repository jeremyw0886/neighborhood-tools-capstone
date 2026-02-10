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
     * Create a new account and return the new ID.
     *
     * Uses subqueries and fn_get_account_status_id() to resolve FK values
     * by name — no hard-coded lookup IDs.
     *
     * @param array{first_name: string, last_name: string, email: string,
     *              password_hash: string, zip_code: string, neighborhood_id: ?int} $data
     */
    public static function create(array $data): int
    {
        $pdo = Database::connection();

        $sql = "
            INSERT INTO account_acc (
                first_name_acc,
                last_name_acc,
                email_address_acc,
                password_hash_acc,
                zip_code_acc,
                id_nbh_acc,
                id_rol_acc,
                id_ast_acc,
                id_cpr_acc
            ) VALUES (
                :first_name,
                :last_name,
                :email,
                :password_hash,
                :zip_code,
                :neighborhood_id,
                (SELECT id_rol FROM role_rol WHERE role_name_rol = 'member'),
                fn_get_account_status_id('pending'),
                (SELECT id_cpr FROM contact_preference_cpr WHERE preference_name_cpr = 'email')
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':first_name', $data['first_name']);
        $stmt->bindValue(':last_name', $data['last_name']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':password_hash', $data['password_hash']);
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
     * Verify a plaintext password against a stored hash.
     */
    public static function verifyPassword(string $input, string $hash): bool
    {
        return password_verify(password: $input, hash: $hash);
    }
}
