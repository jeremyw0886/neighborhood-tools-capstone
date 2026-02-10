<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Account;
use App\Models\Borrow;
use App\Models\Tool;

class DashboardController extends BaseController
{
    /**
     * Main dashboard — summary cards with counts and quick-action links.
     *
     * Shows personal borrow/tool/rating data for all roles.
     * Admin and super_admin roles see additional platform-wide stats
     * (open disputes, pending deposits, open incidents).
     */
    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $userRole = Role::tryFrom($_SESSION['user_role'] ?? '');

        try {
            $activeBorrowCount  = Borrow::getActiveCountForUser($userId);
            $pendingRequestCount = Borrow::getPendingCountForUser($userId);
            $overdueCount       = Borrow::getOverdueCountForUser($userId);
            $listedToolCount    = Tool::getCountByOwner($userId);
            $reputation         = Account::getReputation($userId);
        } catch (\Throwable $e) {
            error_log('DashboardController::index — ' . $e->getMessage());
            $activeBorrowCount   = 0;
            $pendingRequestCount = 0;
            $overdueCount        = 0;
            $listedToolCount     = 0;
            $reputation          = null;
        }

        // Admin-only platform stats
        $adminStats = null;

        if ($userRole?->isAdmin()) {
            try {
                $adminStats = $this->fetchAdminStats();
            } catch (\Throwable $e) {
                error_log('DashboardController::index (admin stats) — ' . $e->getMessage());
            }
        }

        $this->render('dashboard/index', [
            'title'              => 'Dashboard — NeighborhoodTools',
            'description'        => 'Your NeighborhoodTools dashboard — manage borrows, tools, and activity.',
            'pageCss'            => ['dashboard.css'],
            'activeBorrowCount'  => $activeBorrowCount,
            'pendingRequestCount' => $pendingRequestCount,
            'overdueCount'       => $overdueCount,
            'listedToolCount'    => $listedToolCount,
            'reputation'         => $reputation,
            'adminStats'         => $adminStats,
        ]);
    }

    /**
     * Lender sub-page — listed tools and incoming borrow requests.
     */
    public function lender(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        try {
            $tools          = Tool::getByOwner($userId);
            $pendingRequests = Borrow::getPendingForUser($userId);
        } catch (\Throwable $e) {
            error_log('DashboardController::lender — ' . $e->getMessage());
            $tools           = [];
            $pendingRequests = [];
        }

        // Filter pending requests to only those where this user is the lender
        $incomingRequests = array_filter(
            $pendingRequests,
            static fn(array $row): bool => (int) $row['lender_id'] === $userId,
        );

        $this->render('dashboard/lender', [
            'title'            => 'My Tools — NeighborhoodTools',
            'description'      => 'Manage your listed tools and incoming borrow requests.',
            'pageCss'          => ['dashboard.css'],
            'tools'            => $tools,
            'incomingRequests' => array_values($incomingRequests),
        ]);
    }

    /**
     * Borrower sub-page — active borrows and outgoing requests.
     */
    public function borrower(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        try {
            $activeBorrows   = Borrow::getActiveForUser($userId);
            $pendingRequests = Borrow::getPendingForUser($userId);
            $overdue         = Borrow::getOverdueForUser($userId);
        } catch (\Throwable $e) {
            error_log('DashboardController::borrower — ' . $e->getMessage());
            $activeBorrows   = [];
            $pendingRequests = [];
            $overdue         = [];
        }

        // Filter to only rows where this user is the borrower
        $myBorrows = array_filter(
            $activeBorrows,
            static fn(array $row): bool => (int) $row['borrower_id'] === $userId,
        );

        $myRequests = array_filter(
            $pendingRequests,
            static fn(array $row): bool => (int) $row['borrower_id'] === $userId,
        );

        $myOverdue = array_filter(
            $overdue,
            static fn(array $row): bool => (int) $row['borrower_id'] === $userId,
        );

        $this->render('dashboard/borrower', [
            'title'       => 'My Borrows — NeighborhoodTools',
            'description' => 'Track your active borrows and pending requests.',
            'pageCss'     => ['dashboard.css'],
            'borrows'     => array_values($myBorrows),
            'requests'    => array_values($myRequests),
            'overdue'     => array_values($myOverdue),
        ]);
    }

    /**
     * History sub-page — past completed borrows.
     *
     * Uses sp_get_user_borrow_history() to fetch completed/denied/cancelled
     * borrows for the logged-in user in both lender and borrower roles.
     */
    public function history(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        $lenderHistory   = [];
        $borrowerHistory = [];

        try {
            $pdo = \App\Core\Database::connection();

            // Lender history
            $stmt = $pdo->prepare('CALL sp_get_user_borrow_history(:id, :role, :status, :limit, :offset)');
            $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':role', 'lender', \PDO::PARAM_STR);
            $stmt->bindValue(':status', null, \PDO::PARAM_NULL);
            $stmt->bindValue(':limit', 20, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', 0, \PDO::PARAM_INT);
            $stmt->execute();
            $lenderHistory = $stmt->fetchAll();
            $stmt->closeCursor();

            // Borrower history
            $stmt = $pdo->prepare('CALL sp_get_user_borrow_history(:id, :role, :status, :limit, :offset)');
            $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':role', 'borrower', \PDO::PARAM_STR);
            $stmt->bindValue(':status', null, \PDO::PARAM_NULL);
            $stmt->bindValue(':limit', 20, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', 0, \PDO::PARAM_INT);
            $stmt->execute();
            $borrowerHistory = $stmt->fetchAll();
            $stmt->closeCursor();
        } catch (\Throwable $e) {
            error_log('DashboardController::history — ' . $e->getMessage());
        }

        $this->render('dashboard/history', [
            'title'           => 'Borrow History — NeighborhoodTools',
            'description'     => 'View your past lending and borrowing activity.',
            'pageCss'         => ['dashboard.css'],
            'lenderHistory'   => $lenderHistory,
            'borrowerHistory' => $borrowerHistory,
        ]);
    }

    /**
     * Fetch platform-wide stats for admin dashboard cards.
     *
     * @return array{openDisputes: int, pendingDeposits: int, openIncidents: int}
     */
    private function fetchAdminStats(): array
    {
        $pdo = \App\Core\Database::connection();

        $openDisputes = (int) $pdo->query('SELECT COUNT(*) FROM open_dispute_v')->fetchColumn();
        $pendingDeposits = (int) $pdo->query('SELECT COUNT(*) FROM pending_deposit_v')->fetchColumn();
        $openIncidents = (int) $pdo->query('SELECT COUNT(*) FROM open_incident_v')->fetchColumn();

        return [
            'openDisputes'    => $openDisputes,
            'pendingDeposits' => $pendingDeposits,
            'openIncidents'   => $openIncidents,
        ];
    }
}
