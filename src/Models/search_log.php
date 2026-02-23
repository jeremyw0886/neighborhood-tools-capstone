<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class SearchLog
{
    /**
     * Record a tool search for analytics.
     *
     * @param  string  $term       Search text entered by the user
     * @param  ?int    $accountId  Logged-in user ID, or null for guests
     * @param  string  $ipAddress  Client IP address
     * @param  string  $sessionId  PHP session ID
     */
    public static function insert(
        string $term,
        ?int $accountId,
        string $ipAddress,
        string $sessionId,
    ): void {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'INSERT INTO search_log_slg (id_acc_slg, search_text_slg, ip_address_slg, session_id_slg)
             VALUES (:account, :term, :ip, :session)'
        );

        $stmt->bindValue(':account', $accountId, $accountId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':term', mb_substr($term, 0, 255), PDO::PARAM_STR);
        $stmt->bindValue(':ip', $ipAddress, PDO::PARAM_STR);
        $stmt->bindValue(':session', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
    }
}
