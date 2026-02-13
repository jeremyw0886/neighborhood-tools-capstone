<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Role;

class AdminController extends BaseController
{
    /**
     * Admin dashboard — platform-wide summary stats and quick links.
     *
     * Displays total members, available tools, open disputes, pending deposits,
     * open incidents, and upcoming events. Stats are fetched from materialized
     * views and admin-specific views for fast dashboard performance.
     */
    public function dashboard(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        try {
            $stats = $this->fetchPlatformStats();
        } catch (\Throwable $e) {
            error_log('AdminController::dashboard — ' . $e->getMessage());
            $stats = [
                'totalMembers'    => 0,
                'activeMembers'   => 0,
                'availableTools'  => 0,
                'openDisputes'    => 0,
                'pendingDeposits' => 0,
                'openIncidents'   => 0,
                'upcomingEvents'  => 0,
            ];
        }

        $this->render('admin/dashboard', [
            'title'       => 'Admin Dashboard — NeighborhoodTools',
            'description' => 'Platform administration overview and management.',
            'pageCss'     => ['dashboard.css', 'admin.css'],
            'stats'       => $stats,
        ]);
    }

    /**
     * User management — paginated list of platform members.
     *
     * Stub — queries user_reputation_fast_v when fully implemented.
     */
    public function users(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/users', [
            'title'       => 'Manage Users — NeighborhoodTools',
            'description' => 'View and manage platform members.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * Tool management — paginated list of all tools.
     *
     * Stub — queries tool_statistics_fast_v when fully implemented.
     */
    public function tools(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/tools', [
            'title'       => 'Manage Tools — NeighborhoodTools',
            'description' => 'View and manage listed tools.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * Dispute management — open disputes with details.
     *
     * Stub — queries open_dispute_v when fully implemented.
     */
    public function disputes(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/disputes', [
            'title'       => 'Manage Disputes — NeighborhoodTools',
            'description' => 'Review and resolve open disputes.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * Event management — upcoming community events.
     *
     * Stub — queries upcoming_event_v when fully implemented.
     */
    public function events(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/events', [
            'title'       => 'Manage Events — NeighborhoodTools',
            'description' => 'View and manage community events.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * Incident management — open incident reports.
     *
     * Stub — queries open_incident_v when fully implemented.
     */
    public function incidents(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/incidents', [
            'title'       => 'Manage Incidents — NeighborhoodTools',
            'description' => 'Review open incident reports.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * Reports — deposit status and neighborhood statistics.
     *
     * Stub — queries pending_deposit_v and neighborhood_summary_fast_v
     * when fully implemented.
     */
    public function reports(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/reports', [
            'title'       => 'Reports — NeighborhoodTools',
            'description' => 'Platform reports and statistics.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * Audit log — platform activity history.
     *
     * Stub — no audit log table exists in the schema yet.
     */
    public function auditLog(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/audit-log', [
            'title'       => 'Audit Log — NeighborhoodTools',
            'description' => 'Platform activity and audit trail.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * TOS management — current version and acceptance status.
     *
     * Stub — queries current_tos_v and tos_acceptance_required_v
     * when fully implemented.
     */
    public function tos(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/tos', [
            'title'       => 'Manage Terms of Service — NeighborhoodTools',
            'description' => 'View and manage Terms of Service versions.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * Fetch platform-wide summary stats for the admin dashboard.
     *
     * Uses direct COUNT queries against admin views and an aggregate
     * query against neighborhood_summary_fast_v (materialized) for
     * member and tool totals.
     *
     * @return array{totalMembers: int, activeMembers: int, availableTools: int,
     *               openDisputes: int, pendingDeposits: int, openIncidents: int,
     *               upcomingEvents: int}
     */
    private function fetchPlatformStats(): array
    {
        $pdo = Database::connection();

        // Aggregate member and tool totals from materialized neighborhood data
        $row = $pdo->query(
            'SELECT COALESCE(SUM(total_members), 0)  AS total_members,
                    COALESCE(SUM(active_members), 0)  AS active_members,
                    COALESCE(SUM(available_tools), 0)  AS available_tools
               FROM neighborhood_summary_fast_v'
        )->fetch(\PDO::FETCH_ASSOC);

        // Direct counts from admin views
        $openDisputes    = (int) $pdo->query('SELECT COUNT(*) FROM open_dispute_v')->fetchColumn();
        $pendingDeposits = (int) $pdo->query('SELECT COUNT(*) FROM pending_deposit_v')->fetchColumn();
        $openIncidents   = (int) $pdo->query('SELECT COUNT(*) FROM open_incident_v')->fetchColumn();
        $upcomingEvents  = (int) $pdo->query('SELECT COUNT(*) FROM upcoming_event_v')->fetchColumn();

        return [
            'totalMembers'    => (int) $row['total_members'],
            'activeMembers'   => (int) $row['active_members'],
            'availableTools'  => (int) $row['available_tools'],
            'openDisputes'    => $openDisputes,
            'pendingDeposits' => $pendingDeposits,
            'openIncidents'   => $openIncidents,
            'upcomingEvents'  => $upcomingEvents,
        ];
    }
}
