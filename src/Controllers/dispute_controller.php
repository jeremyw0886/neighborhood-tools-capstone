<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
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
}
