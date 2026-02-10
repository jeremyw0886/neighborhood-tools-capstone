<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Notification
{
    /**
     * Count unread notifications for a user.
     *
     * Uses a direct COUNT on notification_ntf rather than the unread_notification_v
     * view — avoids the view's multi-table JOINs since only the count is needed.
     * The covering index idx_unread_timeline_type_ntf on
     * (id_acc_ntf, is_read_ntf, created_at_ntf, id_ntt_ntf) makes this very fast.
     */
    public static function getUnreadCount(int $accountId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM notification_ntf
            WHERE id_acc_ntf = :account_id
              AND is_read_ntf = FALSE
        ");

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch notifications for a user (both read and unread), newest first.
     *
     * Queries notification_ntf directly with the same JOINs that
     * unread_notification_v uses, but WITHOUT the is_read_ntf = FALSE filter
     * so the notifications page can display all items with read/unread styling.
     *
     * @return array  Each row includes: id_ntf, notification_type, title_ntf,
     *                body_ntf, is_read_ntf, created_at_ntf, id_bor_ntf,
     *                related_tool_name, related_borrow_status
     */
    public static function getForUser(int $accountId, int $limit = 12, int $offset = 0): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                ntf.id_ntf,
                ntt.type_name_ntt  AS notification_type,
                ntf.title_ntf,
                ntf.body_ntf,
                ntf.is_read_ntf,
                ntf.created_at_ntf,
                ntf.id_bor_ntf,
                t.tool_name_tol    AS related_tool_name,
                bst.status_name_bst AS related_borrow_status
            FROM notification_ntf ntf
            JOIN notification_type_ntt ntt ON ntf.id_ntt_ntf = ntt.id_ntt
            LEFT JOIN borrow_bor b         ON ntf.id_bor_ntf = b.id_bor
            LEFT JOIN tool_tol t           ON b.id_tol_bor = t.id_tol
            LEFT JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
            WHERE ntf.id_acc_ntf = :account_id
            ORDER BY ntf.created_at_ntf DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count total notifications for a user (read + unread).
     *
     * Used for pagination on the notifications index page.
     */
    public static function getCountForUser(int $accountId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM notification_ntf
            WHERE id_acc_ntf = :account_id
        ");

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Mark notifications as read via sp_mark_notifications_read.
     *
     * Pass null for $notificationIds to mark ALL unread notifications
     * for the user as read, or a comma-separated string of IDs
     * (e.g. "1,5,7") to mark specific ones.
     *
     * Uses MySQL user variables (@count) for the OUT parameter to avoid
     * PDO driver inconsistencies with bindParam OUT across MAMP/SiteGround.
     *
     * @param  int     $accountId        User whose notifications to mark
     * @param  ?string $notificationIds  Comma-separated IDs or null for all
     * @return int     Number of notifications marked as read
     */
    public static function markRead(int $accountId, ?string $notificationIds = null): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('CALL sp_mark_notifications_read(:account_id, :ids, @count)');
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':ids', $notificationIds, $notificationIds === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
        $stmt->closeCursor();

        return (int) $pdo->query('SELECT @count')->fetchColumn();
    }
}
