<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PlatformStats
{
    private const int TREND_DAYS = 14;

    /**
     * Fetch aggregate counts for the admin dashboard summary cards.
     *
     * Delegates to domain models for each operational count.
     *
     * @return array{openDisputes: int, pendingDeposits: int, openIncidents: int}
     */
    public static function getAdminDashboardCounts(): array
    {
        return [
            'openDisputes'    => Dispute::getCount(),
            'pendingDeposits' => Deposit::getPendingCount(),
            'openIncidents'   => Incident::getOpenCount(),
        ];
    }

    /**
     * Fetch the last N days of platform snapshots from platform_daily_stat_pds.
     *
     * Returns rows in reverse-chronological order (most recent first).
     *
     * @return array<int, array{stat_date: string, total_accounts: int,
     *               active_accounts: int, new_accounts_today: int,
     *               total_tools: int, available_tools: int, new_tools_today: int,
     *               active_borrows: int, completed_today: int,
     *               new_requests_today: int, open_disputes: int,
     *               open_incidents: int, overdue_borrows: int,
     *               deposits_held_total: string}>
     */
    public static function getRecentTrends(int $days = self::TREND_DAYS): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT stat_date_pds          AS stat_date,
                    total_accounts_pds     AS total_accounts,
                    active_accounts_pds    AS active_accounts,
                    new_accounts_today_pds AS new_accounts_today,
                    total_tools_pds        AS total_tools,
                    available_tools_pds    AS available_tools,
                    new_tools_today_pds    AS new_tools_today,
                    active_borrows_pds     AS active_borrows,
                    completed_today_pds    AS completed_today,
                    new_requests_today_pds AS new_requests_today,
                    open_disputes_pds      AS open_disputes,
                    open_incidents_pds     AS open_incidents,
                    overdue_borrows_pds    AS overdue_borrows,
                    deposits_held_total_pds AS deposits_held_total
               FROM platform_daily_stat_pds
              WHERE stat_date_pds >= CURDATE() - INTERVAL :days DAY
              ORDER BY stat_date_pds DESC'
        );
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
