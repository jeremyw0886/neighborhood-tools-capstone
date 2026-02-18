<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\RateLimiter;
use App\Models\Borrow;
use App\Models\Deposit;
use App\Models\Notification;
use App\Models\Tool;

class BorrowController extends BaseController
{
    /**
     * Handle a borrow request submission from the tool detail page.
     *
     * Validates input, delegates to Borrow::create() (which calls
     * sp_create_borrow_request), and notifies the tool owner on success.
     * Redirects to the dashboard on success or back to the tool page
     * with flash errors on failure.
     */
    public function request(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = (int) $_SESSION['user_id'];
        $toolId = (int) ($_POST['tool_id'] ?? 0);

        if ($toolId < 1) {
            $this->abort(404);
        }

        $this->checkRateLimit(
            'borrow_request',
            '/tools/' . $toolId,
            'borrow_errors.general',
            'Too many borrow requests. Please try again in {minutes}.',
        );

        $loanDuration = (int) ($_POST['loan_duration'] ?? 0);
        $notes        = trim($_POST['notes'] ?? '');

        $errors = $this->validateRequest($loanDuration);

        if ($errors !== []) {
            $_SESSION['borrow_errors'] = $errors;
            $_SESSION['borrow_old'] = [
                'loan_duration' => $loanDuration,
                'notes'         => $notes,
            ];
            $this->redirect('/tools/' . $toolId);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('BorrowController::request tool lookup — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        if ((int) $tool['owner_id'] === $userId) {
            $_SESSION['borrow_errors'] = ['general' => 'You cannot borrow your own tool.'];
            $this->redirect('/tools/' . $toolId);
        }

        RateLimiter::increment(($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|borrow_request');

        try {
            $result = Borrow::create(
                toolId: $toolId,
                borrowerId: $userId,
                loanDurationHours: $loanDuration,
                notes: $notes !== '' ? $notes : null,
            );
        } catch (\Throwable $e) {
            error_log('BorrowController::request — ' . $e->getMessage());
            $_SESSION['borrow_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $_SESSION['borrow_old'] = [
                'loan_duration' => $loanDuration,
                'notes'         => $notes,
            ];
            $this->redirect('/tools/' . $toolId);
        }

        if ($result['error'] !== null) {
            $_SESSION['borrow_errors'] = ['general' => $result['error']];
            $_SESSION['borrow_old'] = [
                'loan_duration' => $loanDuration,
                'notes'         => $notes,
            ];
            $this->redirect('/tools/' . $toolId);
        }

        $borrowerName = $_SESSION['user_first_name'] ?? 'Someone';

        try {
            Notification::send(
                accountId: (int) $tool['owner_id'],
                type: 'request',
                title: 'New Borrow Request',
                body: $borrowerName . ' wants to borrow your ' . $tool['tool_name_tol'] . '.',
                relatedBorrowId: $result['borrow_id'],
            );
        } catch (\Throwable $e) {
            error_log('BorrowController::request notification — ' . $e->getMessage());
        }

        $_SESSION['borrow_success'] = 'Your borrow request has been sent! The lender will review it shortly.';
        $this->redirect('/dashboard');
    }

    /**
     * Approve a pending borrow request (lender action).
     *
     * Verifies the logged-in user owns the tool, delegates to
     * Borrow::approve() (sp_approve_borrow_request), then notifies
     * the borrower via Notification::send().
     */
    public function approve(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $borrowId = (int) $id;

        if ($borrowId < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $request = Borrow::findPendingById($borrowId);
        } catch (\Throwable $e) {
            error_log('BorrowController::approve lookup — ' . $e->getMessage());
            $request = null;
        }

        if ($request === null) {
            $this->abort(404);
        }

        if ((int) $request['lender_id'] !== $userId) {
            $this->abort(403);
        }

        try {
            $result = Borrow::approve(borrowId: $borrowId, approverId: $userId);
        } catch (\Throwable $e) {
            error_log('BorrowController::approve — ' . $e->getMessage());
            $_SESSION['borrow_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/lender');
        }

        if (!$result['success']) {
            $_SESSION['borrow_errors'] = ['general' => $result['error'] ?? 'Unable to approve this request.'];
            $this->redirect('/dashboard/lender');
        }

        $lenderName = $_SESSION['user_first_name'] ?? 'The lender';

        try {
            Notification::send(
                accountId: (int) $request['borrower_id'],
                type: 'approval',
                title: 'Borrow Request Approved',
                body: $lenderName . ' approved your request to borrow ' . $request['tool_name_tol'] . '.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('BorrowController::approve notification — ' . $e->getMessage());
        }

        $_SESSION['borrow_success'] = 'Request approved! The borrower has been notified.';
        $this->redirect('/dashboard/lender');
    }

    /**
     * Deny a pending borrow request (lender action).
     *
     * Requires a reason. Delegates to Borrow::deny()
     * (sp_deny_borrow_request), then notifies the borrower.
     */
    public function deny(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $borrowId = (int) $id;

        if ($borrowId < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $request = Borrow::findPendingById($borrowId);
        } catch (\Throwable $e) {
            error_log('BorrowController::deny lookup — ' . $e->getMessage());
            $request = null;
        }

        if ($request === null) {
            $this->abort(404);
        }

        if ((int) $request['lender_id'] !== $userId) {
            $this->abort(403);
        }

        $reason = trim($_POST['reason'] ?? '');

        if ($reason === '') {
            $_SESSION['borrow_errors'] = ['reason' => 'Please provide a reason for denying this request.'];
            $this->redirect('/dashboard/lender');
        }

        if (mb_strlen($reason) > 1000) {
            $_SESSION['borrow_errors'] = ['reason' => 'Reason must be 1,000 characters or fewer.'];
            $this->redirect('/dashboard/lender');
        }

        try {
            $result = Borrow::deny(borrowId: $borrowId, denierId: $userId, reason: $reason);
        } catch (\Throwable $e) {
            error_log('BorrowController::deny — ' . $e->getMessage());
            $_SESSION['borrow_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/lender');
        }

        if (!$result['success']) {
            $_SESSION['borrow_errors'] = ['general' => $result['error'] ?? 'Unable to deny this request.'];
            $this->redirect('/dashboard/lender');
        }

        $lenderName = $_SESSION['user_first_name'] ?? 'The lender';

        try {
            Notification::send(
                accountId: (int) $request['borrower_id'],
                type: 'approval',
                title: 'Borrow Request Denied',
                body: $lenderName . ' denied your request to borrow ' . $request['tool_name_tol'] . '. Reason: ' . $reason,
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('BorrowController::deny notification — ' . $e->getMessage());
        }

        $_SESSION['borrow_success'] = 'Request denied. The borrower has been notified.';
        $this->redirect('/dashboard/lender');
    }

    /**
     * Cancel a borrow request (borrower or lender action).
     *
     * Works on "requested" or "approved" borrows. Delegates to
     * Borrow::cancel() (sp_cancel_borrow_request) and notifies
     * the other party.
     */
    public function cancel(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $borrowId = (int) $id;

        if ($borrowId < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $borrow = Borrow::findById($borrowId);
        } catch (\Throwable $e) {
            error_log('BorrowController::cancel lookup — ' . $e->getMessage());
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

        $reason = 'Cancelled by ' . ($isBorrower ? 'borrower' : 'lender');

        try {
            $result = Borrow::cancel(borrowId: $borrowId, cancellerId: $userId, reason: $reason);
        } catch (\Throwable $e) {
            error_log('BorrowController::cancel — ' . $e->getMessage());
            $_SESSION['borrow_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect($isBorrower ? '/dashboard/borrower' : '/dashboard/lender');
        }

        if (!$result['success']) {
            $_SESSION['borrow_errors'] = ['general' => $result['error'] ?? 'Unable to cancel this request.'];
            $this->redirect($isBorrower ? '/dashboard/borrower' : '/dashboard/lender');
        }

        $recipientId   = $isBorrower ? (int) $borrow['lender_id'] : (int) $borrow['borrower_id'];
        $cancellerName = $_SESSION['user_first_name'] ?? 'A user';

        try {
            Notification::send(
                accountId: $recipientId,
                type: 'request',
                title: 'Borrow Request Cancelled',
                body: $cancellerName . ' cancelled the borrow request for ' . $borrow['tool_name_tol'] . '.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('BorrowController::cancel notification — ' . $e->getMessage());
        }

        $_SESSION['borrow_success'] = 'Request cancelled.';
        $this->redirect($isBorrower ? '/dashboard/borrower' : '/dashboard/lender');
    }

    /**
     * Confirm a tool has been returned (lender action).
     *
     * Verifies the logged-in user is the lender for this active borrow,
     * delegates to Borrow::completeReturn() (sp_complete_return), releases
     * any held deposit via Deposit::release(), and notifies the borrower.
     */
    public function return(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $borrowId = (int) $id;

        if ($borrowId < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $borrow = Borrow::findActiveById($borrowId);
        } catch (\Throwable $e) {
            error_log('BorrowController::return lookup — ' . $e->getMessage());
            $borrow = null;
        }

        if ($borrow === null) {
            $this->abort(404);
        }

        if ((int) $borrow['lender_id'] !== $userId) {
            $this->abort(403);
        }

        try {
            $result = Borrow::completeReturn(borrowId: $borrowId);
        } catch (\Throwable $e) {
            error_log('BorrowController::return — ' . $e->getMessage());
            $_SESSION['borrow_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/lender');
        }

        if (!$result['success']) {
            $_SESSION['borrow_errors'] = ['general' => $result['error'] ?? 'Unable to complete this return.'];
            $this->redirect('/dashboard/lender');
        }

        try {
            $depositResult = Deposit::release(borrowId: $borrowId);

            if (!$depositResult['success']) {
                error_log('BorrowController::return deposit release failed — ' . ($depositResult['error'] ?? 'unknown'));
            }
        } catch (\Throwable $e) {
            error_log('BorrowController::return deposit — ' . $e->getMessage());
        }

        $lenderName = $_SESSION['user_first_name'] ?? 'The lender';

        try {
            Notification::send(
                accountId: (int) $borrow['borrower_id'],
                type: 'return',
                title: 'Tool Returned',
                body: $lenderName . ' confirmed the return of ' . $borrow['tool_name_tol'] . '.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('BorrowController::return notification — ' . $e->getMessage());
        }

        $_SESSION['borrow_success'] = 'Return confirmed! The tool has been marked as returned.';
        $this->redirect('/dashboard/lender');
    }

    /**
     * Validate borrow request form fields.
     *
     * @return array<string, string>  Field-keyed error messages (empty = valid)
     */
    private function validateRequest(int $loanDuration): array
    {
        $errors = [];

        if ($loanDuration < 1 || $loanDuration > 720) {
            $errors['loan_duration'] = 'Please select a valid loan duration.';
        }

        return $errors;
    }
}
