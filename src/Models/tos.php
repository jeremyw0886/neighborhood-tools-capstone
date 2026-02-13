<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Tos
{
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
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':tos_id', $tosId, PDO::PARAM_INT);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
        $stmt->bindValue(':user_agent', isset($_SERVER['HTTP_USER_AGENT'])
            ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 512)
            : null);
        $stmt->execute();
    }
}
