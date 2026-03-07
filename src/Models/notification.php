<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Notification
{
    private const array REQUIRED_TYPES = ['request', 'approval', 'denial'];

    private const array TOGGLEABLE_TYPES = ['due', 'return', 'rating'];

    private const array FILTER_CLAUSES = [
        'unread'   => 'AND ntf.is_read_ntf = FALSE',
        'request'  => "AND ntt.type_name_ntt = 'request'",
        'decision' => "AND ntt.type_name_ntt IN ('approval', 'denial')",
        'activity' => "AND ntt.type_name_ntt IN ('due', 'return')",
    ];

    /**
     * Count unread notifications for a user.
     *
     * Uses a direct COUNT on notification_ntf with the covering index
     * idx_unread_timeline_type_ntf rather than unread_notification_v to
     * avoid multi-table JOINs when only the count is needed.
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
     * Fetch a single notification by ID, scoped to the owning account.
     *
     * @return ?array Notification row with notification_type, borrow IDs, participant IDs — or null if not found/not owned
     */
    public static function getById(int $id, int $accountId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT
                ntf.id_ntf,
                ntt.type_name_ntt AS notification_type,
                ntf.title_ntf,
                ntf.body_ntf,
                ntf.is_read_ntf,
                ntf.created_at_ntf,
                ntf.id_bor_ntf,
                t.id_tol AS related_tool_id,
                t.tool_name_tol AS related_tool_name,
                bst.status_name_bst AS related_borrow_status,
                b.id_acc_bor AS related_borrower_id,
                t.id_acc_tol AS related_lender_id
            FROM notification_ntf ntf
            JOIN notification_type_ntt ntt ON ntf.id_ntt_ntf = ntt.id_ntt
            LEFT JOIN borrow_bor b ON ntf.id_bor_ntf = b.id_bor
            LEFT JOIN tool_tol t ON b.id_tol_bor = t.id_tol
            LEFT JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
            WHERE ntf.id_ntf = :id
              AND ntf.id_acc_ntf = :account_id
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Fetch notifications for a user (both read and unread), newest first.
     *
     * Mirrors unread_notification_v column structure (notification_type,
     * hours_ago, related_tool_name, related_borrow_status) but omits the
     * view's is_read_ntf = FALSE filter so both states are returned.
     */
    public static function getForUser(
        int $accountId,
        int $limit = 12,
        int $offset = 0,
        ?string $filter = null,
    ): array {
        $pdo         = Database::connection();
        $filterClause = $filter !== null ? (self::FILTER_CLAUSES[$filter] ?? '') : '';

        $sql = "
            SELECT
                ntf.id_ntf,
                ntt.type_name_ntt AS notification_type,
                ntf.title_ntf,
                ntf.body_ntf,
                ntf.is_read_ntf,
                ntf.created_at_ntf,
                TIMESTAMPDIFF(HOUR, ntf.created_at_ntf, NOW()) AS hours_ago,
                ntf.id_bor_ntf,
                t.id_tol AS related_tool_id,
                t.tool_name_tol AS related_tool_name,
                bst.status_name_bst AS related_borrow_status
            FROM notification_ntf ntf
            JOIN notification_type_ntt ntt ON ntf.id_ntt_ntf = ntt.id_ntt
            LEFT JOIN borrow_bor b ON ntf.id_bor_ntf = b.id_bor
            LEFT JOIN tool_tol t ON b.id_tol_bor = t.id_tol
            LEFT JOIN borrow_status_bst bst ON b.id_bst_bor = bst.id_bst
            WHERE ntf.id_acc_ntf = :account_id
            {$filterClause}
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
     * Fetch only unread notifications for a user via unread_notification_v.
     *
     * Returns the view's full column set: notification_type, hours_ago,
     * related_tool_name, related_borrow_status. Used where only unread
     * items are needed (e.g. nav badge dropdown, AJAX polling).
     */
    public static function getUnreadForUser(int $accountId, int $limit = 5): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT *
            FROM unread_notification_v
            WHERE user_id = :account_id
            ORDER BY created_at_ntf DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count notifications for a user, optionally filtered, for pagination.
     */
    public static function getCountForUser(int $accountId, ?string $filter = null): int
    {
        $pdo          = Database::connection();
        $filterClause = $filter !== null ? (self::FILTER_CLAUSES[$filter] ?? '') : '';
        $needsTypeJoin = $filter !== null && $filter !== 'unread';

        $join = $needsTypeJoin
            ? 'JOIN notification_type_ntt ntt ON ntf.id_ntt_ntf = ntt.id_ntt'
            : '';

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM notification_ntf ntf
            {$join}
            WHERE ntf.id_acc_ntf = :account_id
            {$filterClause}
        ");

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get notification preferences for toggleable types.
     *
     * @return array<string, bool> Keyed by type name, true = enabled
     */
    public static function getPreferences(int $accountId): array
    {
        $pdo = Database::connection();

        $placeholders = implode(',', array_fill(0, count(self::TOGGLEABLE_TYPES), '?'));

        $stmt = $pdo->prepare("
            SELECT ntt.type_name_ntt, ntp.is_enabled_ntp
            FROM notification_preference_ntp ntp
            JOIN notification_type_ntt ntt ON ntp.id_ntt_ntp = ntt.id_ntt
            WHERE ntp.id_acc_ntp = ?
              AND ntt.type_name_ntt IN ({$placeholders})
        ");

        $params = [$accountId, ...self::TOGGLEABLE_TYPES];
        $stmt->execute($params);

        $rows = $stmt->fetchAll();

        $prefs = [];
        foreach (self::TOGGLEABLE_TYPES as $type) {
            $prefs[$type] = true;
        }
        foreach ($rows as $row) {
            $prefs[$row['type_name_ntt']] = (bool) $row['is_enabled_ntp'];
        }

        return $prefs;
    }

    /**
     * Update notification preferences for toggleable types.
     *
     * @param array<string, bool> $prefs Keyed by type name, true = enabled
     */
    public static function updatePreferences(int $accountId, array $prefs): void
    {
        $pdo = Database::connection();

        foreach ($prefs as $type => $enabled) {
            if (!in_array($type, self::TOGGLEABLE_TYPES, true)) {
                continue;
            }

            if ($enabled) {
                $stmt = $pdo->prepare("
                    DELETE ntp FROM notification_preference_ntp ntp
                    JOIN notification_type_ntt ntt ON ntp.id_ntt_ntp = ntt.id_ntt
                    WHERE ntp.id_acc_ntp = :account_id
                      AND ntt.type_name_ntt = :type
                ");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO notification_preference_ntp (id_acc_ntp, id_ntt_ntp, is_enabled_ntp)
                    SELECT :account_id, id_ntt, FALSE
                    FROM notification_type_ntt
                    WHERE type_name_ntt = :type
                    ON DUPLICATE KEY UPDATE is_enabled_ntp = FALSE
                ");
            }

            $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    /**
     * Check if a notification type is enabled for a user.
     *
     * @return bool True if the type is enabled (or required)
     */
    public static function isTypeEnabled(int $accountId, string $type): bool
    {
        if (in_array($type, self::REQUIRED_TYPES, true)) {
            return true;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT ntp.is_enabled_ntp
            FROM notification_preference_ntp ntp
            JOIN notification_type_ntt ntt ON ntp.id_ntt_ntp = ntt.id_ntt
            WHERE ntp.id_acc_ntp = :account_id
              AND ntt.type_name_ntt = :type
        ");

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row === false || (bool) $row['is_enabled_ntp'];
    }

    /**
     * Send a notification via sp_send_notification.
     *
     * @param  string $type             Notification type name (e.g. 'request', 'approval')
     * @param  ?int   $relatedBorrowId  Associated borrow record (null if none)
     * @return ?int   The new notification ID, or null on failure
     */
    public static function send(
        int $accountId,
        string $type,
        string $title,
        string $body,
        ?int $relatedBorrowId = null,
    ): ?int {
        if (!self::isTypeEnabled($accountId, $type)) {
            return null;
        }

        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'CALL sp_send_notification(:account_id, :type, :title, :body, :borrow_id, @notification_id)'
        );

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':body', $body, PDO::PARAM_STR);
        $stmt->bindValue(':borrow_id', $relatedBorrowId, $relatedBorrowId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        $out = $pdo->query('SELECT @notification_id AS nid')->fetchColumn();

        return $out !== false && $out !== null ? (int) $out : null;
    }

    /**
     * Check whether a notification with a given title already exists for a borrow.
     *
     * @return bool True if a matching notification exists
     */
    public static function existsForBorrow(int $accountId, string $title, int $borrowId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT 1
            FROM notification_ntf
            WHERE id_acc_ntf = :account_id
              AND title_ntf  = :title
              AND id_bor_ntf = :borrow_id
            LIMIT 1
        ");

        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':borrow_id', $borrowId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Delete a single notification, scoped to the owning account.
     *
     * @return bool True if a row was deleted
     */
    public static function delete(int $id, int $accountId): bool
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            DELETE FROM notification_ntf
            WHERE id_ntf = :id
              AND id_acc_ntf = :account_id
        ");

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all read notifications for a user via sp_clear_read_notifications.
     *
     * @return int Number of notifications deleted
     */
    public static function clearRead(int $accountId): int
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('CALL sp_clear_read_notifications(:account_id, @deleted_count)');
        $stmt->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();

        return (int) $pdo->query('SELECT @deleted_count')->fetchColumn();
    }

    /**
     * Mark notifications as read via sp_mark_notifications_read.
     *
     * @param  ?string $notificationIds  Comma-separated IDs, or null for all
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
