<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Account;
use App\Models\Borrow;
use App\Models\Deposit;
use App\Models\PlatformStats;
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
            $activeBorrowCount   = Borrow::getActiveCountForUser($userId, 'borrower');
            $pendingRequestCount = Borrow::getPendingCountForUser($userId, 'lender');
            $overdueCount        = Borrow::getOverdueCountForUser($userId, 'borrower');
            $approvedCount       = Borrow::getApprovedCountForUser($userId, 'borrower');
            $listedToolCount    = Tool::getCountByOwner($userId);
            $reputation         = Account::getReputation($userId);
        } catch (\Throwable $e) {
            error_log('DashboardController::index — ' . $e->getMessage());
            $activeBorrowCount   = 0;
            $pendingRequestCount = 0;
            $overdueCount        = 0;
            $approvedCount       = 0;
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
            'title'               => 'Dashboard — NeighborhoodTools',
            'description'         => 'Your NeighborhoodTools dashboard — manage borrows, tools, and activity.',
            'pageCss'             => ['dashboard.css'],
            'activeBorrowCount'   => $activeBorrowCount,
            'pendingRequestCount' => $pendingRequestCount,
            'overdueCount'        => $overdueCount,
            'approvedCount'       => $approvedCount,
            'listedToolCount'     => $listedToolCount,
            'reputation'          => $reputation,
            'adminStats'          => $adminStats,
            'borrowSuccess'       => $this->flash('borrow_success'),
            'ratingSuccess'       => $this->flash('rating_success'),
            'waiverSuccess'       => $this->flash('waiver_success'),
        ]);
    }

    /**
     * Lender sub-page — listed tools and incoming borrow requests.
     */
    public function lender(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        $reqSort = $this->parseSortParams('req_', [
            'requested_at_bor', 'tool_name_tol', 'borrower_name',
            'hours_pending', 'loan_duration_hours_bor',
        ], 'requested_at_bor', 'DESC');

        $lentSort = $this->parseSortParams('lent_', [
            'due_at_bor', 'tool_name_tol', 'borrower_name', 'hours_until_due',
        ], 'due_at_bor', 'ASC');

        try {
            $tools           = Tool::getByOwner($userId);
            $pendingRequests = Borrow::getPendingForUser($userId, $reqSort['sort'], $reqSort['dir']);
            $activeBorrows   = Borrow::getActiveForUser($userId, $lentSort['sort'], $lentSort['dir']);
            $approvedLoans   = Borrow::getApprovedForUser($userId);
        } catch (\Throwable $e) {
            error_log('DashboardController::lender — ' . $e->getMessage());
            $tools           = [];
            $pendingRequests = [];
            $activeBorrows   = [];
            $approvedLoans   = [];
        }

        $incomingRequests = array_filter(
            $pendingRequests,
            static fn(array $row): bool => (int) $row['lender_id'] === $userId,
        );

        $lentOut = array_filter(
            $activeBorrows,
            static fn(array $row): bool => (int) $row['lender_id'] === $userId,
        );

        $awaitingPickup = array_filter(
            $approvedLoans,
            static fn(array $row): bool => (int) $row['lender_id'] === $userId,
        );

        $this->render('dashboard/lender', [
            'title'            => 'My Tools — NeighborhoodTools',
            'description'      => 'Manage your listed tools and incoming borrow requests.',
            'pageCss'          => ['dashboard.css'],
            'tools'            => $tools,
            'incomingRequests' => array_values($incomingRequests),
            'awaitingPickup'   => array_values($awaitingPickup),
            'lentOut'          => array_values($lentOut),
            'reqSort'          => $reqSort,
            'lentSort'         => $lentSort,
            'borrowSuccess'    => $this->flash('borrow_success'),
            'borrowErrors'     => $this->flash('borrow_errors', []),
        ]);
    }

    /**
     * Borrower sub-page — active borrows and outgoing requests.
     */
    public function borrower(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        $borrowSort = $this->parseSortParams('borrow_', [
            'due_at_bor', 'tool_name_tol', 'lender_name', 'hours_until_due',
        ], 'due_at_bor', 'ASC');

        $reqSort = $this->parseSortParams('req_', [
            'requested_at_bor', 'tool_name_tol', 'lender_name', 'loan_duration_hours_bor',
        ], 'requested_at_bor', 'DESC');

        $borrowStatus = $this->parseStatusFilter('borrow_', ['on-time', 'due-soon', 'overdue']);

        try {
            $activeBorrows   = Borrow::getActiveForUser($userId, $borrowSort['sort'], $borrowSort['dir']);
            $pendingRequests = Borrow::getPendingForUser($userId, $reqSort['sort'], $reqSort['dir']);
            $overdue         = Borrow::getOverdueForUser($userId);
            $approvedLoans   = Borrow::getApprovedForUser($userId);
        } catch (\Throwable $e) {
            error_log('DashboardController::borrower — ' . $e->getMessage());
            $activeBorrows   = [];
            $pendingRequests = [];
            $overdue         = [];
            $approvedLoans   = [];
        }

        $myBorrows = array_filter(
            $activeBorrows,
            static fn(array $row): bool => (int) $row['borrower_id'] === $userId,
        );

        if ($borrowStatus !== null) {
            $statusMap = ['on-time' => 'ON TIME', 'due-soon' => 'DUE SOON', 'overdue' => 'OVERDUE'];
            $dbStatus  = $statusMap[$borrowStatus];
            $myBorrows = array_filter(
                $myBorrows,
                static fn(array $row): bool => ($row['due_status'] ?? '') === $dbStatus,
            );
        }

        $myRequests = array_filter(
            $pendingRequests,
            static fn(array $row): bool => (int) $row['borrower_id'] === $userId,
        );

        $myOverdue = array_filter(
            $overdue,
            static fn(array $row): bool => (int) $row['borrower_id'] === $userId,
        );

        $awaitingPickup = array_filter(
            $approvedLoans,
            static fn(array $row): bool => (int) $row['borrower_id'] === $userId,
        );

        $pickupBorrowIds   = array_map(static fn(array $row): int => (int) $row['id_bor'], $awaitingPickup);
        $depositsByBorrow  = Deposit::findByBorrowIds($pickupBorrowIds);

        $this->render('dashboard/borrower', [
            'title'            => 'My Borrows — NeighborhoodTools',
            'description'      => 'Track your active borrows and pending requests.',
            'pageCss'          => ['dashboard.css'],
            'borrows'          => array_values($myBorrows),
            'requests'         => array_values($myRequests),
            'overdue'          => array_values($myOverdue),
            'awaitingPickup'   => array_values($awaitingPickup),
            'depositsByBorrow' => $depositsByBorrow,
            'borrowSort'       => $borrowSort,
            'borrowStatus'     => $borrowStatus,
            'reqSort'          => $reqSort,
            'borrowSuccess'    => $this->flash('borrow_success'),
            'borrowErrors'     => $this->flash('borrow_errors', []),
        ]);
    }

    /**
     * History sub-page — past completed borrows.
     *
     * Uses Borrow::getUserHistory() (wraps sp_get_user_borrow_history) to fetch
     * completed/denied/cancelled borrows for the logged-in user in both roles.
     */
    public function history(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        $lendSort = $this->parseSortParams('lend_', [
            'requested_at_bor', 'tool_name_tol', 'borrower_name', 'borrow_status',
        ], 'requested_at_bor', 'DESC');

        $borrowSort = $this->parseSortParams('borrow_', [
            'requested_at_bor', 'tool_name_tol', 'lender_name', 'borrow_status',
        ], 'requested_at_bor', 'DESC');

        $lendStatus   = $this->parseStatusFilter('lend_', ['returned', 'denied', 'cancelled']);
        $borrowStatus = $this->parseStatusFilter('borrow_', ['returned', 'denied', 'cancelled']);

        $lenderHistory   = [];
        $borrowerHistory = [];

        try {
            $lenderHistory = Borrow::getUserHistory(
                $userId, 'lender', $lendStatus, 20, 0,
                $lendSort['sort'], $lendSort['dir'],
            );
            $borrowerHistory = Borrow::getUserHistory(
                $userId, 'borrower', $borrowStatus, 20, 0,
                $borrowSort['sort'], $borrowSort['dir'],
            );
        } catch (\Throwable $e) {
            error_log('DashboardController::history — ' . $e->getMessage());
        }

        $this->render('dashboard/history', [
            'title'           => 'Borrow History — NeighborhoodTools',
            'description'     => 'View your past lending and borrowing activity.',
            'pageCss'         => ['dashboard.css'],
            'lenderHistory'   => $lenderHistory,
            'borrowerHistory' => $borrowerHistory,
            'lendSort'        => $lendSort,
            'lendStatus'      => $lendStatus,
            'borrowSort'      => $borrowSort,
            'borrowStatus'    => $borrowStatus,
        ]);
    }

    /**
     * Loan detail page — full status timeline for a single borrow.
     */
    public function loanStatus(string $id): void
    {
        $this->requireAuth();

        $borrowId = (int) $id;
        if ($borrowId < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $borrow = Borrow::findById($borrowId);
        } catch (\Throwable $e) {
            error_log('DashboardController::loanStatus — ' . $e->getMessage());
            $this->abort(500);
        }

        if ($borrow === null) {
            $this->abort(404);
        }

        if ((int) $borrow['borrower_id'] !== $userId && (int) $borrow['lender_id'] !== $userId) {
            $this->abort(403);
        }

        $extensions = [];
        $handovers  = [];
        $deposit    = null;

        try {
            $extensions = Borrow::getExtensions($borrowId);
            $handovers  = Borrow::getHandovers($borrowId);
            $deposit    = Deposit::findByBorrowId($borrowId);
        } catch (\Throwable $e) {
            error_log('DashboardController::loanStatus (details) — ' . $e->getMessage());
        }

        $this->render('dashboard/loan-status', [
            'title'       => 'Loan Status — NeighborhoodTools',
            'description' => 'Track the status of your borrow.',
            'pageCss'     => ['dashboard.css'],
            'borrow'      => $borrow,
            'extensions'  => $extensions,
            'handovers'   => $handovers,
            'deposit'     => $deposit,
        ]);
    }

    /**
     * Fetch platform-wide stats for admin dashboard cards.
     *
     * @return array{openDisputes: int, pendingDeposits: int, openIncidents: int}
     */
    private function fetchAdminStats(): array
    {
        return PlatformStats::getAdminDashboardCounts();
    }
}
