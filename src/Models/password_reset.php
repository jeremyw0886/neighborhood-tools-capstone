<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PasswordReset
{
    private const TOKEN_LIFETIME_MINUTES = 60;

    /**
     * Generate a reset token for an account.
     *
     * Invalidates any existing unused tokens for the account, then creates
     * a new one. The raw token is returned for inclusion in the reset URL;
     * only its SHA-256 hash is stored in the database.
     *
     * @return string  Raw 64-character hex token (for the reset link)
     */
    public static function createToken(int $accountId): string
    {
        $pdo = Database::connection();

        $pdo->prepare("
            UPDATE password_reset_pwr
            SET used_at_pwr = NOW()
            WHERE id_acc_pwr = :id AND used_at_pwr IS NULL
        ")->execute([':id' => $accountId]);

        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO password_reset_pwr (id_acc_pwr, token_hash_pwr, expires_at_pwr)
            VALUES (:id, :hash, DATE_ADD(NOW(), INTERVAL :minutes MINUTE))
        ");

        $stmt->bindValue(':id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':hash', hash('sha256', $token), PDO::PARAM_STR);
        $stmt->bindValue(':minutes', self::TOKEN_LIFETIME_MINUTES, PDO::PARAM_INT);
        $stmt->execute();

        return $token;
    }

    /**
     * Find a valid (unused, unexpired) reset token.
     *
     * Hashes the raw token and looks up the matching row. Returns the
     * reset record joined with the account email, or null if invalid.
     *
     * @return ?array{id_pwr: int, id_acc_pwr: int, email_address_acc: string}
     */
    public static function findValidToken(string $token): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT pr.id_pwr, pr.id_acc_pwr, a.email_address_acc
            FROM password_reset_pwr pr
            JOIN account_acc a ON pr.id_acc_pwr = a.id_acc
            WHERE pr.token_hash_pwr = :hash
              AND pr.expires_at_pwr > NOW()
              AND pr.used_at_pwr IS NULL
            LIMIT 1
        ");

        $stmt->bindValue(':hash', hash('sha256', $token), PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Mark a reset token as used so it cannot be reused.
     */
    public static function markUsed(int $resetId): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            UPDATE password_reset_pwr
            SET used_at_pwr = NOW()
            WHERE id_pwr = :id
        ");

        $stmt->bindValue(':id', $resetId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Delete expired and used tokens older than 24 hours.
     *
     * Call periodically (e.g. on forgot-password POST) to keep the table lean.
     */
    public static function cleanup(): void
    {
        Database::connection()->exec("
            DELETE FROM password_reset_pwr
            WHERE used_at_pwr IS NOT NULL
               OR expires_at_pwr < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
    }
}
