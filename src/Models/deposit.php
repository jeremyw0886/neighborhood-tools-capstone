<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Deposit
{
    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM pending_deposit_v WHERE id_sdp = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Release a held security deposit via sp_release_deposit_on_return().
     *
     * The SP checks for a deposit linked to the borrow. If none exists,
     * it returns success silently. If one is held, it transitions the
     * deposit to "released" and records the timestamp.
     *
     * @return array{success: bool, error: ?string}
     */
    public static function release(int $borrowId): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_release_deposit_on_return(:borrow_id, @success, @error_msg)'
        );

        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @success AS success, @error_msg AS error')->fetch();

        return [
            'success' => (bool) $out['success'],
            'error'   => $out['error'],
        ];
    }
}
