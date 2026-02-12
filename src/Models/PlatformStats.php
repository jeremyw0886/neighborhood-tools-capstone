<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class PlatformStats
{
    /**
     * Fetch aggregate counts for the admin dashboard summary cards.
     *
     * Queries open_dispute_v, pending_deposit_v, and open_incident_v
     * to give admins a quick operational overview.
     *
     * @return array{openDisputes: int, pendingDeposits: int, openIncidents: int}
     */
    public static function getAdminDashboardCounts(): array
    {
        $pdo = Database::connection();

        $openDisputes    = (int) $pdo->query('SELECT COUNT(*) FROM open_dispute_v')->fetchColumn();
        $pendingDeposits = (int) $pdo->query('SELECT COUNT(*) FROM pending_deposit_v')->fetchColumn();
        $openIncidents   = (int) $pdo->query('SELECT COUNT(*) FROM open_incident_v')->fetchColumn();

        return [
            'openDisputes'    => $openDisputes,
            'pendingDeposits' => $pendingDeposits,
            'openIncidents'   => $openIncidents,
        ];
    }
}
