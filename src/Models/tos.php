<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Tos
{
    private const int PER_PAGE = 12;

    private const array NON_COMPLIANT_SORT_FIELDS = [
        'full_name',
        'last_login_at_acc',
        'last_accepted_version',
    ];

    /**
     * Fetch paginated active users who have not accepted the current TOS.
     *
     * @return array  Rows with id_acc, full_name, email_address_acc,
     *                last_login_at_acc, created_at_acc,
     *                last_tos_accepted_at, last_accepted_version
     */
    public static function getNonCompliantUsers(int $limit = self::PER_PAGE, int $offset = 0, string $sort = 'last_login_at_acc', string $dir = 'DESC'): array
    {
        $sortCol = in_array($sort, self::NON_COMPLIANT_SORT_FIELDS, true) ? $sort : 'last_login_at_acc';
        $sortDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            "SELECT *
               FROM tos_acceptance_required_v
              ORDER BY {$sortCol} {$sortDir}, full_name ASC
              LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count active users who have not accepted the current TOS.
     */
    public static function getNonCompliantCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query('SELECT COUNT(*) FROM tos_acceptance_required_v')->fetchColumn();
    }

    /**
     * Fetch the current active Terms of Service version.
     *
     * Queries the current_tos_v view, which filters to only
     * the active, non-superseded version.
     *
     * @return ?array{id_tos: int, version_tos: string, title_tos: string,
     *               content_tos: string, summary_tos: ?string,
     *               effective_at_tos: string, created_at_tos: string,
     *               created_by_name: string, total_acceptances: int}
     */
    public static function getCurrent(): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT *
            FROM current_tos_v
            LIMIT 1
        ");

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Check whether a user has accepted a specific TOS version.
     *
     * The tos_acceptance_tac table has a UNIQUE constraint on
     * (id_acc_tac, id_tos_tac), so at most one row exists per pair.
     */
    public static function hasUserAccepted(int $accountId, int $tosId): bool
    {
        $pdo = Database::connection();

        $sql = "
            SELECT 1
            FROM tos_acceptance_tac
            WHERE id_acc_tac = :account_id
              AND id_tos_tac = :tos_id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':tos_id', $tosId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    /**
     * Create a new TOS version, superseding the current active one.
     *
     * @param string  $version     Version identifier (e.g. "2.0")
     * @param string  $title       Document title
     * @param string  $content     Full TOS text
     * @param ?string $summary     Plain-language summary
     * @param string  $effectiveAt MySQL TIMESTAMP string
     * @param int     $createdBy   Account ID of the creating admin
     */
    public static function createVersion(
        string $version,
        string $title,
        string $content,
        ?string $summary,
        string $effectiveAt,
        int $createdBy,
    ): void {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('CALL sp_create_tos_version(:version, :title, :content, :summary, :effective_at, :created_by)');
        $stmt->bindValue(':version', $version);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':summary', $summary, $summary === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':effective_at', $effectiveAt);
        $stmt->bindValue(':created_by', $createdBy, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * Record a user's acceptance of a TOS version.
     *
     * Captures IP address and user agent for the audit trail.
     * The UNIQUE constraint on (id_acc_tac, id_tos_tac) prevents
     * duplicate acceptances — a duplicate INSERT will throw a PDOException.
     */
    public static function recordAcceptance(int $accountId, int $tosId): void
    {
        $pdo = Database::connection();

        $sql = "
            INSERT INTO tos_acceptance_tac (
                id_acc_tac,
                id_tos_tac,
                ip_address_tac,
                user_agent_tac
            ) VALUES (
                :account_id,
                :tos_id,
                :ip_address,
                :user_agent
            )
        ";

        $stmt = $pdo->prepare($sql);
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
            ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 512)
            : null;

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':tos_id', $tosId, PDO::PARAM_INT);
        $stmt->bindValue(':ip_address', $ip, $ip === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $userAgent, $userAgent === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }
}
