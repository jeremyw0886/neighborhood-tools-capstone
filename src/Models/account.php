<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Role;
use PDO;

class Account
{
    /**
     * Fetch paginated members with reputation and role data for admin users page.
     *
     * @param  string  $sort   Pre-validated column name
     * @param  string  $dir    Pre-validated direction (ASC|DESC)
     * @return array   Rows with reputation fields plus role_name_rol
     */
    public static function getAllForAdmin(
        int $limit,
        int $offset,
        string $sort = 'full_name',
        string $dir = 'ASC',
        ?string $role = null,
        ?string $status = null,
        ?string $search = null,
        bool $excludeDeleted = true,
    ): array {
        $pdo = Database::connection();

        $filter      = self::buildFilterWhere($role, $status, $search, $excludeDeleted);
        $whereClause = $filter['sql'];

        $qualified = match ($sort) {
            'role_name_rol'    => 'rol.role_name_rol',
            'full_name'        => "CONCAT(a.first_name_acc, ' ', a.last_name_acc)",
            'account_status'   => 'ast.status_name_ast',
            'email_address_acc' => 'a.email_address_acc',
            'member_since'     => 'a.created_at_acc',
            default            => 'r.' . $sort,
        };

        $sql = "
            SELECT
                a.id_acc,
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS full_name,
                a.email_address_acc,
                ast.status_name_ast AS account_status,
                a.created_at_acc    AS member_since,
                a.is_purged_acc,
                rol.role_name_rol,
                COALESCE(r.lender_avg_rating, 0)     AS lender_avg_rating,
                COALESCE(r.lender_rating_count, 0)    AS lender_rating_count,
                COALESCE(r.borrower_avg_rating, 0)    AS borrower_avg_rating,
                COALESCE(r.borrower_rating_count, 0)   AS borrower_rating_count,
                r.overall_avg_rating,
                COALESCE(r.total_rating_count, 0)     AS total_rating_count,
                COALESCE(r.tools_owned, 0)            AS tools_owned,
                COALESCE(r.completed_borrows, 0)      AS completed_borrows
            FROM account_acc a
            JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
            JOIN role_rol rol           ON a.id_rol_acc = rol.id_rol
            LEFT JOIN user_reputation_v r ON a.id_acc = r.id_acc
            $whereClause
            ORDER BY $qualified $dir
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($filter['params'] as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Search accounts by name or email for admin global search.
     *
     * @return array<int, array{id_acc: int, full_name: string, email_address_acc: string, role_name_rol: string, account_status: string}>
     */
    public static function adminSearch(string $term, int $limit = 5): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                a.id_acc,
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS full_name,
                a.email_address_acc,
                r.role_name_rol,
                ast.status_name_ast AS account_status
            FROM active_account_v a
            JOIN role_rol r             ON a.id_rol_acc = r.id_rol
            JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
            WHERE CONCAT(a.first_name_acc, ' ', a.last_name_acc) LIKE CONCAT('%', :term1, '%')
               OR a.email_address_acc LIKE CONCAT('%', :term2, '%')
            ORDER BY CONCAT(a.first_name_acc, ' ', a.last_name_acc) ASC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $escaped = Database::escapeLike($term);
        $stmt->bindValue(':term1', $escaped);
        $stmt->bindValue(':term2', $escaped);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count members matching the given filters for admin pagination.
     *
     * @return int
     */
    public static function getFilteredCount(
        ?string $role = null,
        ?string $status = null,
        ?string $search = null,
        bool $excludeDeleted = true,
    ): int {
        $pdo = Database::connection();

        $filter      = self::buildFilterWhere($role, $status, $search, $excludeDeleted);
        $whereClause = $filter['sql'];

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM account_acc a
            JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
            JOIN role_rol rol           ON a.id_rol_acc = rol.id_rol
            $whereClause
        ");

        foreach ($filter['params'] as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Build the shared WHERE clause used by getAllForAdmin() and getFilteredCount().
     *
     * @return array{sql: string, params: array<string, array{0: mixed, 1: int}>}
     */
    private static function buildFilterWhere(
        ?string $role,
        ?string $status,
        ?string $search,
        bool $excludeDeleted,
    ): array {
        $where  = [];
        $params = [];

        if ($excludeDeleted) {
            $where[] = "ast.status_name_ast != 'deleted'";
        }

        if ($role !== null) {
            $where[]         = 'rol.role_name_rol = :role';
            $params[':role'] = [$role, PDO::PARAM_STR];
        }

        if ($status !== null) {
            $where[]           = 'ast.status_name_ast = :status';
            $params[':status'] = [$status, PDO::PARAM_STR];
        }

        if ($search !== null) {
            $where[]            = "(CONCAT(a.first_name_acc, ' ', a.last_name_acc) LIKE CONCAT('%', :search1, '%')
                                 OR a.email_address_acc LIKE CONCAT('%', :search2, '%'))";
            $escaped = Database::escapeLike($search);
            $params[':search1'] = [$escaped, PDO::PARAM_STR];
            $params[':search2'] = [$escaped, PDO::PARAM_STR];
        }

        return [
            'sql'    => $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '',
            'params' => $params,
        ];
    }

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
                a.zip_code_acc,
                r.role_name_rol,
                ast.status_name_ast AS account_status,
                aim.file_name_aim      AS avatar,
                avv.file_name_avv      AS vector_avatar
            FROM active_account_v a
            JOIN role_rol r            ON a.id_rol_acc = r.id_rol
            JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
            LEFT JOIN account_image_aim aim
                ON a.id_acc = aim.id_acc_aim AND aim.is_primary_aim = TRUE
            LEFT JOIN avatar_vector_avv avv
                ON a.id_avv_acc = avv.id_avv
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
     * Find a non-deleted account by username — used for login authentication.
     *
     * @return ?array{id_acc: int, first_name_acc: string, last_name_acc: string,
     *               username_acc: string, password_hash_acc: string,
     *               role_name_rol: string, account_status: string, avatar: ?string}
     */
    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                a.id_acc,
                a.first_name_acc,
                a.last_name_acc,
                a.username_acc,
                a.password_hash_acc,
                a.zip_code_acc,
                r.role_name_rol,
                ast.status_name_ast AS account_status,
                aim.file_name_aim      AS avatar,
                avv.file_name_avv      AS vector_avatar
            FROM active_account_v a
            JOIN role_rol r            ON a.id_rol_acc = r.id_rol
            JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast
            LEFT JOIN account_image_aim aim
                ON a.id_acc = aim.id_acc_aim AND aim.is_primary_aim = TRUE
            LEFT JOIN avatar_vector_avv avv
                ON a.id_avv_acc = avv.id_avv
            WHERE a.username_acc = :username
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $username);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Find a non-deleted account by username or email — used for flexible login.
     *
     * @return ?array Account row with role, status, and avatar, or null.
     */
    public static function findByLogin(string $input): ?array
    {
        return self::findByUsername($input) ?? self::findByEmail($input);
    }

    /**
     * Find an account by ID using the full profile view.
     *
     * @return ?array Full profile row from account_profile_v, or null.
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
                COALESCE(
                    :neighborhood_id,
                    (SELECT id_nbh_nbhzpc
                     FROM neighborhood_zip_nbhzpc
                     WHERE zip_code_nbhzpc = :zip_nbh
                       AND is_primary_nbhzpc = TRUE
                     LIMIT 1)
                ),
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
        $stmt->bindValue(':zip_nbh', $data['zip_code']);
        $stmt->bindValue(':neighborhood_id', $data['neighborhood_id'], $data['neighborhood_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) $pdo->lastInsertId();
    }

    /**
     * Fetch real-time reputation data for a single user.
     *
     * Queries user_reputation_v (live aggregation) instead of the materialized
     * _fast_v so that ratings, tool counts, and borrow totals reflect the
     * latest state — important for profile pages and dashboards.
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
            FROM user_reputation_v
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
     * Fetch top-rated active members for Friendly Neighbors + sidebar fallback.
     *
     * @param  int      $limit            Max members to return
     * @param  int|null $excludeAccountId Account ID to omit (logged-in user)
     * @return array<int, array{id_acc: int, username: string, avatar: ?string,
     *               vector_avatar: ?string, avg_rating: float, bio: ?string,
     *               tools_owned: int, completed_borrows: int,
     *               total_rating_count: int, is_top_member: int}>
     */
    public static function getTopMembers(int $limit = 3, ?int $excludeAccountId = null): array
    {
        $pdo = Database::connection();

        $excludeClause = $excludeAccountId !== null
            ? 'AND p.id_acc != :exclude_id'
            : '';

        $sql = "
            SELECT
                p.id_acc,
                p.username_acc                   AS username,
                p.primary_image                  AS avatar,
                p.vector_avatar,
                COALESCE(r.overall_avg_rating, 0) AS avg_rating,
                p.bio_text_abi                   AS bio,
                COALESCE(r.tools_owned, 0)        AS tools_owned,
                COALESCE(r.completed_borrows, 0)  AS completed_borrows,
                COALESCE(r.total_rating_count, 0) AS total_rating_count,
                CASE
                    WHEN COALESCE(r.overall_avg_rating, 0) >= 4.0
                     AND COALESCE(r.total_rating_count, 0) >= 1
                    THEN 1 ELSE 0
                END AS is_top_member
            FROM account_profile_v p
            LEFT JOIN user_reputation_fast_v r ON p.id_acc = r.id_acc
            WHERE p.account_status = 'active'
              AND p.role_name_rol  = 'member'
              $excludeClause
            ORDER BY
                COALESCE(r.overall_avg_rating, 0) DESC,
                COALESCE(r.total_rating_count, 0) DESC,
                (COALESCE(r.tools_owned, 0) + COALESCE(r.completed_borrows, 0)) DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        if ($excludeAccountId !== null) {
            $stmt->bindValue(':exclude_id', $excludeAccountId, PDO::PARAM_INT);
        }

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
     * @param  string   $city             City name (e.g. "Asheville", "Hendersonville")
     * @param  int      $limit            Max members to return (default 10)
     * @param  int|null $excludeAccountId Account ID to omit (logged-in user)
     * @return array<int, array{id_acc: int, username: string, avatar: ?string,
     *               avg_rating: float, total_rating_count: int,
     *               neighborhood: ?string, is_top_member: int,
     *               distance_miles: float}>
     */
    /**
     * Session-cached wrapper around getNearbyMembers().
     *
     * @param  int $ttl  Cache lifetime in seconds
     * @return array
     */
    public static function getCachedNearbyMembers(string $city, int $limit = 10, ?int $excludeAccountId = null, int $ttl = 300): array
    {
        $cacheKey   = '_nearby_' . strtolower($city);
        $cacheTimeKey = $cacheKey . '_at';

        if (isset($_SESSION[$cacheKey], $_SESSION[$cacheTimeKey])
            && time() - $_SESSION[$cacheTimeKey] < $ttl
        ) {
            return $_SESSION[$cacheKey];
        }

        $members = self::getNearbyMembers($city, $limit, $excludeAccountId);
        $_SESSION[$cacheKey]     = $members;
        $_SESSION[$cacheTimeKey] = time();

        return $members;
    }

    public static function getNearbyMembers(string $city, int $limit = 10, ?int $excludeAccountId = null): array
    {
        $pdo = Database::connection();
        $downtown = 'Downtown ' . $city;

        $excludeClause = $excludeAccountId !== null
            ? 'AND p.id_acc != :exclude_id'
            : '';

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
                p.vector_avatar,
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
              $excludeClause
            HAVING distance_miles IS NOT NULL
            ORDER BY distance_miles ASC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':downtown', $downtown);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        if ($excludeAccountId !== null) {
            $stmt->bindValue(':exclude_id', $excludeAccountId, PDO::PARAM_INT);
        }

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

    /**
     * Fetch editable profile fields for the logged-in user.
     *
     * Queries account_acc directly (not the profile view) to get raw
     * FK values needed for form pre-selection, then LEFT JOINs bio,
     * primary image, and contact preference for display.
     */
    public static function getEditableProfile(int $accountId): ?array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                a.id_acc,
                a.username_acc,
                a.email_address_acc,
                a.first_name_acc,
                a.last_name_acc,
                a.phone_number_acc,
                a.street_address_acc,
                a.zip_code_acc,
                a.id_cpr_acc,
                cpr.preference_name_cpr,
                abi.bio_text_abi,
                aim.file_name_aim           AS primary_image,
                aim.alt_text_aim            AS image_alt_text,
                aim.focal_x_aim,
                aim.focal_y_aim,
                a.id_avv_acc,
                avv.file_name_avv           AS vector_avatar,
                avv.description_text_avv    AS vector_avatar_alt
            FROM active_account_v a
            JOIN contact_preference_cpr cpr ON a.id_cpr_acc = cpr.id_cpr
            LEFT JOIN account_bio_abi abi   ON a.id_acc = abi.id_acc_abi
            LEFT JOIN account_image_aim aim
                ON a.id_acc = aim.id_acc_aim AND aim.is_primary_aim = TRUE
            LEFT JOIN avatar_vector_avv avv
                ON a.id_avv_acc = avv.id_avv
            WHERE a.id_acc = :id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Fetch all contact preference options for a dropdown.
     *
     * @return array<int, array{id_cpr: int, preference_name_cpr: string}>
     */
    public static function getContactPreferences(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT id_cpr, preference_name_cpr
            FROM contact_preference_cpr
            ORDER BY id_cpr
        ");

        return $stmt->fetchAll();
    }

    /**
     * Fetch all account meta key/value pairs for a user.
     *
     * @return array<int, array{meta_key_acm: string, meta_value_acm: string}>
     */
    public static function getAccountMeta(int $accountId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT meta_key_acm, meta_value_acm
            FROM account_meta_acm
            WHERE id_acc_acm = :id
            ORDER BY meta_key_acm
        ");

        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update basic account fields (name, phone, address, contact preference).
     */
    public static function updateProfile(int $accountId, array $data): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE account_acc
            SET first_name_acc     = :first_name,
                last_name_acc      = :last_name,
                phone_number_acc   = :phone,
                street_address_acc = :street_address,
                zip_code_acc       = :zip_code,
                id_nbh_acc = (
                    SELECT id_nbh_nbhzpc
                    FROM neighborhood_zip_nbhzpc
                    WHERE zip_code_nbhzpc = :zip_nbh
                      AND is_primary_nbhzpc = TRUE
                    LIMIT 1
                ),
                id_cpr_acc = (
                    SELECT id_cpr
                    FROM contact_preference_cpr
                    WHERE preference_name_cpr = :preference
                )
            WHERE id_acc = :id
        ");

        $stmt->bindValue(':first_name', $data['first_name']);
        $stmt->bindValue(':last_name', $data['last_name']);
        $stmt->bindValue(':phone', $data['phone'], $data['phone'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':street_address', $data['street_address'], $data['street_address'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':zip_code', $data['zip_code']);
        $stmt->bindValue(':zip_nbh', $data['zip_code']);
        $stmt->bindValue(':preference', $data['contact_preference']);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Insert or update the user's bio text.
     *
     * Uses INSERT ... ON DUPLICATE KEY UPDATE since account_bio_abi
     * has a unique constraint on id_acc_abi.
     */
    public static function upsertBio(int $accountId, ?string $text): void
    {
        $pdo = Database::connection();

        if ($text === null || trim($text) === '') {
            $stmt = $pdo->prepare("DELETE FROM account_bio_abi WHERE id_acc_abi = :id");
            $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
            $stmt->execute();
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO account_bio_abi (id_acc_abi, bio_text_abi)
            VALUES (:id, :bio)
            ON DUPLICATE KEY UPDATE bio_text_abi = VALUES(bio_text_abi)
        ");

        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':bio', trim($text));
        $stmt->execute();
    }

    /** Set or clear the user's chosen avatar vector. */
    public static function setVectorAvatar(int $accountId, ?int $avatarVectorId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE account_acc
            SET id_avv_acc = :vectorId
            WHERE id_acc = :id
        ");

        $stmt->bindValue(':vectorId', $avatarVectorId, $avatarVectorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Save a profile avatar image.
     *
     * If a primary image already exists for this account, updates it.
     * Otherwise inserts a new row with is_primary_aim = TRUE.
     */
    public static function saveProfileImage(
        int $accountId,
        string $filename,
        ?string $altText = null,
        int $focalX = 50,
        int $focalY = 50,
    ): void {
        $pdo = Database::connection();

        $existing = $pdo->prepare("
            SELECT id_aim, file_name_aim
            FROM account_image_aim
            WHERE id_acc_aim = :id
            ORDER BY is_primary_aim DESC
            LIMIT 1
        ");

        $existing->bindValue(':id', $accountId, PDO::PARAM_INT);
        $existing->execute();
        $current = $existing->fetch();
        $existing->closeCursor();

        if ($current !== false) {
            $stmt = $pdo->prepare("
                UPDATE account_image_aim
                SET file_name_aim  = :filename,
                    alt_text_aim   = :alt,
                    focal_x_aim    = :focal_x,
                    focal_y_aim    = :focal_y,
                    is_primary_aim = TRUE
                WHERE id_aim = :aim_id
            ");

            $stmt->bindValue(':filename', $filename);
            $stmt->bindValue(':alt', $altText, $altText !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':focal_x', $focalX, PDO::PARAM_INT);
            $stmt->bindValue(':focal_y', $focalY, PDO::PARAM_INT);
            $stmt->bindValue(':aim_id', (int) $current['id_aim'], PDO::PARAM_INT);
            $stmt->execute();

            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO account_image_aim
                (id_acc_aim, file_name_aim, alt_text_aim, is_primary_aim, focal_x_aim, focal_y_aim)
            VALUES (:id, :filename, :alt, TRUE, :focal_x, :focal_y)
        ");

        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':filename', $filename);
        $stmt->bindValue(':alt', $altText, $altText !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':focal_x', $focalX, PDO::PARAM_INT);
        $stmt->bindValue(':focal_y', $focalY, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Update an account's status using the lookup function.
     *
     * @param string $status  One of: 'pending', 'active', 'suspended', 'deleted'
     */
    public static function updateStatus(int $accountId, string $status): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE account_acc
            SET id_ast_acc = fn_get_account_status_id(:status)
            WHERE id_acc = :id
        ");

        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Change an account's role.
     *
     * @param int  $accountId
     * @param Role $role
     * @return bool
     */
    public static function updateRole(int $accountId, Role $role): bool
    {
        if ($role === Role::SuperAdmin) {
            throw new \InvalidArgumentException('Cannot assign super_admin role via updateRole()');
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE account_acc
            SET id_rol_acc = (SELECT id_rol FROM role_rol WHERE role_name_rol = :role)
            WHERE id_acc = :id
        ");

        $stmt->bindValue(':role', $role->value);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Find an account by ID, including deleted accounts.
     *
     * @return ?array Account row with role and status, or null.
     */
    public static function findByIdIncludeDeleted(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                a.id_acc,
                a.first_name_acc,
                a.last_name_acc,
                CONCAT(a.first_name_acc, ' ', a.last_name_acc) AS full_name,
                a.email_address_acc,
                a.username_acc,
                a.is_purged_acc,
                aim.file_name_aim AS avatar,
                r.role_name_rol,
                s.status_name_ast AS account_status
            FROM account_acc a
            JOIN role_rol r            ON a.id_rol_acc = r.id_rol
            JOIN account_status_ast s  ON a.id_ast_acc = s.id_ast
            LEFT JOIN account_image_aim aim
                ON a.id_acc = aim.id_acc_aim AND aim.is_primary_aim = TRUE
            WHERE a.id_acc = :id
            LIMIT 1
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Anonymize a soft-deleted account, replacing all PII with placeholders.
     *
     * @param int $accountId
     * @return bool
     */
    public static function purge(int $accountId): bool
    {
        $pdo = Database::connection();

        $guard = $pdo->prepare("
            SELECT s.status_name_ast
            FROM account_acc a
            JOIN account_status_ast s ON a.id_ast_acc = s.id_ast
            WHERE a.id_acc = :id
            LIMIT 1
        ");
        $guard->bindValue(':id', $accountId, PDO::PARAM_INT);
        $guard->execute();
        $row = $guard->fetch();

        if ($row === false || $row['status_name_ast'] !== 'deleted') {
            return false;
        }

        $randomHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            UPDATE account_acc
            SET first_name_acc     = 'Deleted',
                last_name_acc      = 'User',
                email_address_acc  = CONCAT('deleted_', id_acc, '@removed.local'),
                username_acc       = CONCAT('deleted_user_', id_acc),
                password_hash_acc  = :hash,
                phone_number_acc   = NULL,
                street_address_acc = NULL,
                id_avv_acc         = NULL,
                is_purged_acc      = TRUE
            WHERE id_acc = :id
        ");

        $stmt->bindValue(':hash', $randomHash);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        $changed = $stmt->rowCount() > 0;

        $bio = $pdo->prepare("DELETE FROM account_bio_abi WHERE id_acc_abi = :id");
        $bio->bindValue(':id', $accountId, PDO::PARAM_INT);
        $bio->execute();

        $img = $pdo->prepare("DELETE FROM account_image_aim WHERE id_acc_aim = :id");
        $img->bindValue(':id', $accountId, PDO::PARAM_INT);
        $img->execute();

        return $changed;
    }

    /**
     * Fetch account IDs for all active admins and super admins.
     *
     * @return array<int>
     */
    public static function getAdminIds(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT a.id_acc
            FROM active_account_v a
            JOIN role_rol r ON a.id_rol_acc = r.id_rol
            WHERE r.role_name_rol IN ('admin', 'super_admin')
        ");

        return array_column($stmt->fetchAll(), 'id_acc');
    }

    /**
     * Count accounts with 'pending' status for admin dashboard badges.
     */
    public static function getPendingCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query(
            "SELECT COUNT(*) FROM account_acc
             WHERE id_ast_acc = fn_get_account_status_id('pending')"
        )->fetchColumn();
    }

    /**
     * Get the current primary image data for an account.
     *
     * @return ?array{file_name_aim: string, focal_x_aim: int, focal_y_aim: int}
     */
    public static function getPrimaryImage(int $accountId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT file_name_aim, focal_x_aim, focal_y_aim
            FROM account_image_aim
            WHERE id_acc_aim = :id AND is_primary_aim = TRUE
            LIMIT 1
        ");

        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Get the profile image row regardless of primary flag.
     */
    public static function getProfileImage(int $accountId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT file_name_aim, focal_x_aim, focal_y_aim, is_primary_aim
            FROM account_image_aim
            WHERE id_acc_aim = :id
            ORDER BY is_primary_aim DESC
            LIMIT 1
        ");

        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Toggle the primary flag on a user's profile image.
     */
    public static function setImageActive(int $accountId, bool $active): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE account_image_aim
            SET is_primary_aim = :active
            WHERE id_acc_aim = :id
        ");

        $stmt->bindValue(':active', $active, PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Update the focal point for an account's profile image.
     */
    public static function updateProfileFocalPoint(int $accountId, int $focalX, int $focalY): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE account_image_aim
            SET focal_x_aim = :focal_x,
                focal_y_aim = :focal_y
            WHERE id_acc_aim = :id
        ");

        $stmt->bindValue(':focal_x', $focalX, PDO::PARAM_INT);
        $stmt->bindValue(':focal_y', $focalY, PDO::PARAM_INT);
        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Delete the primary image record for an account.
     */
    public static function deleteProfileImage(int $accountId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            DELETE FROM account_image_aim
            WHERE id_acc_aim = :id
        ");

        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
