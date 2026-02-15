<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\RateLimiter;
use App\Models\Borrow;
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
