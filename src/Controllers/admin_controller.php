<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Account;
use App\Models\Deposit;
use App\Models\Dispute;
use App\Models\Event;
use App\Models\Incident;
use App\Models\Neighborhood;
use App\Models\PlatformStats;
use App\Models\Tool;
use App\Models\Tos;

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

        try {
            $trends = PlatformStats::getRecentTrends();
        } catch (\Throwable $e) {
            error_log('AdminController::dashboard trends — ' . $e->getMessage());
            $trends = [];
        }

        $this->render('admin/dashboard', [
            'title'       => 'Admin Dashboard — NeighborhoodTools',
            'description' => 'Platform administration overview and management.',
            'pageCss'     => ['dashboard.css', 'admin.css'],
            'stats'       => $stats,
            'trends'      => $trends,
        ]);
    }

    /**
     * User management — paginated list of platform members.
     *
     * Queries user_reputation_fast_v for a paginated table with ratings
     * and activity summaries. Pending rows surface approve/deny actions.
     */
    public function users(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $totalCount = Account::getActiveCount();
        $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        $flash = $_SESSION['admin_users_flash'] ?? null;
        unset($_SESSION['admin_users_flash']);

        $this->render('admin/users', [
            'title'       => 'Manage Users — NeighborhoodTools',
            'description' => 'View and manage platform members.',
            'pageCss'     => ['admin.css'],
            'users'       => Account::getAllForAdmin(self::PER_PAGE, $offset),
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
            'flash'       => $flash,
        ]);
    }

    /**
     * Approve a pending account — sets status to 'active'.
     */
    public function approveUser(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $accountId = (int) $id;

        if ($accountId < 1) {
            $this->abort(404);
        }

        $user = Account::findById($accountId);

        if ($user === null) {
            $this->abort(404);
        }

        if ($user['account_status'] !== 'pending') {
            $_SESSION['admin_users_flash'] = 'Account is not pending approval.';
            $this->redirect('/admin/users');
        }

        try {
            Account::updateStatus($accountId, 'active');
            $_SESSION['admin_users_flash'] = htmlspecialchars($user['full_name']) . ' has been approved.';
        } catch (\Throwable $e) {
            error_log('AdminController::approveUser — ' . $e->getMessage());
            $_SESSION['admin_users_flash'] = 'Failed to approve account.';
        }

        $this->redirect('/admin/users');
    }

    /**
     * Deny a pending account — sets status to 'suspended'.
     */
    public function denyUser(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $accountId = (int) $id;

        if ($accountId < 1) {
            $this->abort(404);
        }

        $user = Account::findById($accountId);

        if ($user === null) {
            $this->abort(404);
        }

        if ($user['account_status'] !== 'pending') {
            $_SESSION['admin_users_flash'] = 'Account is not pending approval.';
            $this->redirect('/admin/users');
        }

        try {
            Account::updateStatus($accountId, 'suspended');
            $_SESSION['admin_users_flash'] = htmlspecialchars($user['full_name']) . ' has been denied.';
        } catch (\Throwable $e) {
            error_log('AdminController::denyUser — ' . $e->getMessage());
            $_SESSION['admin_users_flash'] = 'Failed to deny account.';
        }

        $this->redirect('/admin/users');
    }

    /**
     * Toggle a user's status between active and suspended.
     *
     * Permission hierarchy:
     *   - Admin: can toggle members only (not admins or super admins)
     *   - Super Admin: can toggle members and admins (not other super admins)
     *   - Nobody can modify their own account
     */
    public function updateUserStatus(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $accountId = (int) $id;

        if ($accountId < 1) {
            $this->abort(404);
        }

        $user = Account::findById($accountId);

        if ($user === null) {
            $this->abort(404);
        }

        $targetStatus = $user['account_status'];
        $targetRole   = $user['role_name_rol'];
        $actorRole    = Role::from($_SESSION['user_role']);
        $actorId      = (int) $_SESSION['user_id'];

        if ($actorId === $accountId) {
            $_SESSION['admin_users_flash'] = 'You cannot modify your own account status.';
            $this->redirect('/admin/users');
        }

        if (!in_array($targetStatus, ['active', 'suspended'], true)) {
            $_SESSION['admin_users_flash'] = 'Only active or suspended accounts can be toggled.';
            $this->redirect('/admin/users');
        }

        if ($targetRole === 'super_admin') {
            $_SESSION['admin_users_flash'] = 'Super admin accounts cannot be modified.';
            $this->redirect('/admin/users');
        }

        if ($actorRole === Role::Admin && $targetRole !== 'member') {
            $_SESSION['admin_users_flash'] = 'Admins can only manage member accounts.';
            $this->redirect('/admin/users');
        }

        $newStatus = $targetStatus === 'active' ? 'suspended' : 'active';
        $action    = $newStatus === 'suspended' ? 'suspended' : 'activated';

        try {
            Account::updateStatus($accountId, $newStatus);
            $_SESSION['admin_users_flash'] = htmlspecialchars($user['full_name']) . ' has been ' . $action . '.';
        } catch (\Throwable $e) {
            error_log('AdminController::updateUserStatus — ' . $e->getMessage());
            $_SESSION['admin_users_flash'] = 'Failed to update account status.';
        }

        $this->redirect('/admin/users');
    }

    /**
     * Tool management — paginated list of all tools with analytics.
     *
     * Queries tool_statistics_fast_v (materialized every 2 hours) for
     * borrow counts, ratings, incidents, and condition per tool.
     */
    public function tools(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $totalCount = Tool::getAdminCount();
        $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        $this->render('admin/tools', [
            'title'       => 'Manage Tools — NeighborhoodTools',
            'description' => 'View and manage listed tools.',
            'pageCss'     => ['admin.css'],
            'tools'       => Tool::getAdminList(self::PER_PAGE, $offset),
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
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
     * Reports — neighborhood statistics from materialized summaries.
     *
     * Displays a paginated table of per-neighborhood stats pulled from
     * neighborhood_summary_fast_v via the Neighborhood model.
     */
    public function reports(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $totalCount = Neighborhood::getSummaryCount();
        $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        $this->render('admin/reports', [
            'title'          => 'Reports — NeighborhoodTools',
            'description'    => 'Platform reports and statistics.',
            'pageCss'        => ['admin.css'],
            'neighborhoods'  => Neighborhood::getSummaryList(self::PER_PAGE, $offset),
            'totalCount'     => $totalCount,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'perPage'        => self::PER_PAGE,
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

    private const int PER_PAGE = 12;

    /**
     * TOS management — current version and non-compliant users.
     *
     * Displays the active TOS version summary alongside a paginated
     * list of active members who have not yet accepted it, pulled
     * from tos_acceptance_required_v via the Tos model.
     */
    public function tos(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $totalCount = Tos::getNonCompliantCount();
        $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        $this->render('admin/tos', [
            'title'       => 'Manage Terms of Service — NeighborhoodTools',
            'description' => 'View and manage Terms of Service versions.',
            'pageCss'     => ['admin.css'],
            'users'       => Tos::getNonCompliantUsers(self::PER_PAGE, $offset),
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
        ]);
    }

    /**
     * Fetch platform-wide summary stats for the admin dashboard.
     *
     * Delegates to domain models: Neighborhood for member/tool totals
     * (materialized), Dispute/Deposit/Incident/Event for operational counts.
     *
     * @return array{totalMembers: int, activeMembers: int, availableTools: int,
     *               openDisputes: int, pendingDeposits: int, openIncidents: int,
     *               upcomingEvents: int}
     */
    private function fetchPlatformStats(): array
    {
        $totals = Neighborhood::getPlatformTotals();

        return [
            ...$totals,
            'openDisputes'    => Dispute::getCount(),
            'pendingDeposits' => Deposit::getPendingCount(),
            'openIncidents'   => Incident::getOpenCount(),
            'upcomingEvents'  => Event::getUpcomingCount(),
        ];
    }
}
