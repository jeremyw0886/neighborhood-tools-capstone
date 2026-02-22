<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Waiver;

class WaiverController extends BaseController
{
    /**
     * Display the borrow waiver form for a pending borrow transaction.
     *
     * Only the borrower may view this page. The borrow must be in
     * "approved" status with no existing signed waiver. The view
     * shows tool context, pre-existing conditions, deposit info,
     * and three required acknowledgment checkboxes.
     */
    public function show(string $borrowId): void
    {
        $this->requireAuth();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $waiver = Waiver::findPendingByBorrowId($id);
        } catch (\Throwable $e) {
            error_log('WaiverController::show waiver lookup — ' . $e->getMessage());
            $waiver = null;
        }

        if ($waiver === null) {
            $this->abort(404);
        }

        if ((int) $waiver['borrower_id'] !== $userId) {
            $this->abort(403);
        }

        try {
            $waiverTypes = Waiver::getTypes();
        } catch (\Throwable $e) {
            error_log('WaiverController::show types lookup — ' . $e->getMessage());
            $waiverTypes = [];
        }

        $this->render('waivers/show', [
            'title'       => 'Borrow Waiver — NeighborhoodTools',
            'description' => 'Review and sign the borrow waiver before picking up your tool.',
            'pageCss'     => ['waiver.css'],
            'waiver'      => $waiver,
            'waiverTypes' => $waiverTypes,
        ]);
    }
}
