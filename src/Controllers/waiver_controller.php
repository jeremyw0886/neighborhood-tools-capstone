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

    /**
     * Validate and record a signed borrow waiver.
     *
     * Expects POST fields: csrf_token, tool_condition, responsibility,
     * liability. All three checkboxes must be checked. On success,
     * inserts into borrow_waiver_bwv and redirects to the dashboard.
     */
    public function sign(string $borrowId): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        $toolCondition  = !empty($_POST['tool_condition']);
        $responsibility = !empty($_POST['responsibility']);
        $liability      = !empty($_POST['liability']);

        if (!$toolCondition || !$responsibility || !$liability) {
            $_SESSION['waiver_errors'] = ['general' => 'All three acknowledgments are required to sign the waiver.'];
            $this->redirect('/waiver/' . $id);
        }

        try {
            $waiver = Waiver::findPendingByBorrowId($id);
        } catch (\Throwable $e) {
            error_log('WaiverController::sign waiver lookup — ' . $e->getMessage());
            $waiver = null;
        }

        if ($waiver === null) {
            $this->abort(404);
        }

        if ((int) $waiver['borrower_id'] !== $userId) {
            $this->abort(403);
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);

        try {
            Waiver::sign(
                borrowId: $id,
                accountId: $userId,
                preexistingConditions: $waiver['preexisting_conditions_tol'],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $_SESSION['waiver_success'] = 'Waiver signed successfully. You may now coordinate pickup with the lender.';
            $this->redirect('/dashboard');
        } catch (\Throwable $e) {
            error_log('WaiverController::sign — ' . $e->getMessage());
            $_SESSION['waiver_errors'] = ['general' => 'Something went wrong signing the waiver. Please try again.'];
            $this->redirect('/waiver/' . $id);
        }
    }
}
