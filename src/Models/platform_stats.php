<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PlatformStats
{
    private const int TREND_DAYS = 14;
    private const string DEFAULT_AVG_RATING = '4.0';

    /**
     * Fetch all summary stats for the admin dashboard cards.
     *
     * @return array{totalMembers: int, activeMembers: int, availableTools: int,
     *               openDisputes: int, pendingDeposits: int, openIncidents: int,
     *               upcomingEvents: int}
     */
    public static function getAdminDashboardCounts(): array
    {
        $pdo = Database::connection();

        $row = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM open_dispute_v) AS open_disputes,
                (SELECT COUNT(*) FROM pending_deposit_v) AS pending_deposits,
                (SELECT COUNT(*) FROM open_incident_v) AS open_incidents,
                (SELECT COUNT(*) FROM upcoming_event_v) AS upcoming_events
        ")->fetch(PDO::FETCH_ASSOC);

        return [
            ...Neighborhood::getPlatformTotals(),
            'openDisputes'    => (int) $row['open_disputes'],
            'pendingDeposits' => (int) $row['pending_deposits'],
            'openIncidents'   => (int) $row['open_incidents'],
            'upcomingEvents'  => (int) $row['upcoming_events'],
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

    /**
     * Latest captured platform-wide avg tool rating, for Bayesian priors in
     * ranking queries. Reads the most recent non-NULL value from the daily
     * stats table; falls back to DEFAULT_AVG_RATING when the table is empty
     * or the daily cron hasn't captured a rating yet.
     *
     * Cached per request so callers don't hit the DB repeatedly within a page.
     */
    public static function getAvgToolRating(): float
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $pdo = Database::connection();
            $row = $pdo->query(
                'SELECT platform_avg_rating_pds
                   FROM platform_daily_stat_pds
                  WHERE platform_avg_rating_pds IS NOT NULL
                  ORDER BY stat_date_pds DESC
                  LIMIT 1'
            )->fetch(PDO::FETCH_ASSOC);

            $cached = $row !== false
                ? (float) $row['platform_avg_rating_pds']
                : (float) self::DEFAULT_AVG_RATING;
        } catch (\PDOException) {
            $cached = (float) self::DEFAULT_AVG_RATING;
        }

        return $cached;
    }
}
