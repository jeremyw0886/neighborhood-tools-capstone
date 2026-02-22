<?php

declare(strict_types=1);

namespace App\Models;

class PlatformStats
{
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
}
