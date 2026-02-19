<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Borrow;
use App\Models\Dispute;

class DisputeController extends BaseController
{
    private const int PER_PAGE = 12;

    /**
     * List open disputes with pagination.
     *
     * Admin-only — queries open_dispute_v for all open disputes
     * with reporter, borrower, lender details, message counts,
     * related incidents, and deposit information.
     */
    public function index(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        try {
            $disputes   = Dispute::getAll(limit: self::PER_PAGE, offset: $offset);
            $totalCount = Dispute::getCount();
        } catch (\Throwable $e) {
            error_log('DisputeController::index — ' . $e->getMessage());
            $disputes   = [];
            $totalCount = 0;
        }

        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->render('admin/disputes', [
            'title'       => 'Manage Disputes — NeighborhoodTools',
            'description' => 'Review and resolve open disputes.',
            'pageCss'     => ['admin.css'],
            'disputes'    => $disputes,
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
        ]);
    }

    /**
     * Display the dispute creation form for a borrow transaction.
     *
     * Any authenticated user who is a party (borrower or lender) to the
     * borrow may file a dispute. The DB trigger trg_dispute_before_insert
     * enforces this at the write layer; this method enforces it at the
     * read layer so unauthorized users never see the form.
     */
    public function create(string $borrowId): void
    {
        $this->requireAuth();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $borrow = Borrow::findById($id);
        } catch (\Throwable $e) {
            error_log('DisputeController::create borrow lookup — ' . $e->getMessage());
            $borrow = null;
        }

        if ($borrow === null) {
            $this->abort(404);
        }

        $isBorrower = (int) $borrow['borrower_id'] === $userId;
        $isLender   = (int) $borrow['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        try {
            $hasExisting = Dispute::hasOpenDispute($id);
        } catch (\Throwable $e) {
            error_log('DisputeController::create dispute check — ' . $e->getMessage());
            $hasExisting = false;
        }

        $errors = $_SESSION['dispute_errors'] ?? [];
        $old    = $_SESSION['dispute_old'] ?? [];
        unset($_SESSION['dispute_errors'], $_SESSION['dispute_old']);

        $this->render('disputes/create', [
            'title'       => 'File a Dispute — NeighborhoodTools',
            'description' => 'Report an issue with a borrow transaction.',
            'pageCss'     => ['dispute.css'],
            'borrow'      => $borrow,
            'hasExisting'  => $hasExisting,
            'errors'      => $errors,
            'old'         => $old,
        ]);
    }
}
