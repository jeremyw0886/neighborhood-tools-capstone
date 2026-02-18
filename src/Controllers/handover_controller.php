<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Borrow;
use App\Models\Deposit;
use App\Models\Handover;
use App\Models\Notification;

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

    /**
     * Confirm a handover by verifying the submitted code.
     *
     * Validates the code against the pending handover record, marks
     * it as verified, then delegates to sp_complete_pickup() (for
     * pickup) or sp_complete_return() (for return) to advance the
     * borrow status. Releases held deposits on successful returns.
     * Notifies the other party on success.
     */
    public function confirm(string $borrowId): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $handover = Handover::findPendingByBorrowId($id);
        } catch (\Throwable $e) {
            error_log('HandoverController::confirm lookup — ' . $e->getMessage());
            $handover = null;
        }

        if ($handover === null) {
            $_SESSION['handover_errors'] = ['general' => 'No pending handover found for this borrow.'];
            $this->redirect('/dashboard');
        }

        $isBorrower = (int) $handover['borrower_id'] === $userId;
        $isLender   = (int) $handover['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        $isVerifier = (int) $handover['generator_id'] !== $userId;

        if (!$isVerifier) {
            $_SESSION['handover_errors'] = ['general' => 'Only the receiving party can verify the code.'];
            $this->redirect('/handover/' . $id);
        }

        $code = trim($_POST['code'] ?? '');

        if ($code === '') {
            $_SESSION['handover_errors'] = ['code' => 'Please enter the verification code.'];
            $this->redirect('/handover/' . $id);
        }

        if ($handover['code_status'] === 'EXPIRED') {
            $_SESSION['handover_errors'] = ['code' => 'This verification code has expired. Please ask for a new one.'];
            $this->redirect('/handover/' . $id);
        }

        $storedCode = strtoupper($handover['verification_code_hov']);
        $inputCode  = strtoupper($code);

        if (!hash_equals($storedCode, str_pad($inputCode, strlen($storedCode)))) {
            $_SESSION['handover_errors'] = ['code' => 'Incorrect verification code. Please try again.'];
            $_SESSION['handover_old'] = ['code' => $code];
            $this->redirect('/handover/' . $id);
        }

        try {
            $verified = Handover::markVerified(
                handoverId: (int) $handover['id_hov'],
                verifierId: $userId,
            );
        } catch (\Throwable $e) {
            error_log('HandoverController::confirm markVerified — ' . $e->getMessage());
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/handover/' . $id);
        }

        if (!$verified) {
            $_SESSION['handover_errors'] = ['general' => 'This handover has already been verified.'];
            $this->redirect('/dashboard');
        }

        $isPickup = $handover['handover_type'] === 'pickup';

        try {
            $result = $isPickup
                ? Borrow::completePickup(borrowId: $id)
                : Borrow::completeReturn(borrowId: $id);
        } catch (\Throwable $e) {
            error_log('HandoverController::confirm SP — ' . $e->getMessage());
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/handover/' . $id);
        }

        if (!$result['success']) {
            $_SESSION['handover_errors'] = ['general' => $result['error'] ?? 'Unable to complete this handover.'];
            $this->redirect('/handover/' . $id);
        }

        if (!$isPickup) {
            try {
                $depositResult = Deposit::release(borrowId: $id);

                if (!$depositResult['success']) {
                    error_log('HandoverController::confirm deposit release — ' . ($depositResult['error'] ?? 'unknown'));
                }
            } catch (\Throwable $e) {
                error_log('HandoverController::confirm deposit — ' . $e->getMessage());
            }
        }

        $recipientId = $isBorrower ? (int) $handover['lender_id'] : (int) $handover['borrower_id'];
        $userName    = $_SESSION['user_first_name'] ?? 'A user';
        $toolName    = $handover['tool_name_tol'];
        $typeLabel   = $isPickup ? 'pickup' : 'return';

        try {
            Notification::send(
                accountId: $recipientId,
                type: $isPickup ? 'approval' : 'return',
                title: ucfirst($typeLabel) . ' Confirmed',
                body: $userName . ' verified the ' . $typeLabel . ' of ' . $toolName . '.',
                relatedBorrowId: $id,
            );
        } catch (\Throwable $e) {
            error_log('HandoverController::confirm notification — ' . $e->getMessage());
        }

        $_SESSION['handover_success'] = ucfirst($typeLabel) . ' confirmed! The ' . htmlspecialchars($toolName) . ' handover is complete.';
        $this->redirect('/handover/' . $id);
    }
}
