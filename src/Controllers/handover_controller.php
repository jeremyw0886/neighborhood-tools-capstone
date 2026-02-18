<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Handover;

class HandoverController extends BaseController
{
    /**
     * Display the handover verification page for a borrow.
     *
     * Shows the pending verification code, its status (ACTIVE,
     * EXPIRING SOON, EXPIRED), the handover type (pickup/return),
     * and a form for the verifier to enter the code.
     *
     * Both the borrower and lender involved in the borrow may
     * access this page — one generated the code, the other verifies it.
     */
    public function verify(string $borrowId): void
    {
        $this->requireAuth();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $handover = Handover::findPendingByBorrowId($id);
        } catch (\Throwable $e) {
            error_log('HandoverController::verify lookup — ' . $e->getMessage());
            $handover = null;
        }

        if ($handover === null) {
            $this->abort(404);
        }

        $isBorrower = (int) $handover['borrower_id'] === $userId;
        $isLender   = (int) $handover['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        $isVerifier = (int) $handover['generator_id'] !== $userId;

        $this->render('handover/verify', [
            'title'       => 'Verify Handover — NeighborhoodTools',
            'description' => 'Enter the handover verification code to confirm tool ' . $handover['handover_type'] . '.',
            'pageCss'     => ['handover.css'],
            'handover'    => $handover,
            'isVerifier'  => $isVerifier,
        ]);
    }
}
