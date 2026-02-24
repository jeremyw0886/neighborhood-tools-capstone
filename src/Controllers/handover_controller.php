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
     * If a pending handover exists, shows the code (to the generator)
     * or a code-entry form (to the verifier). If none exists and the
     * borrow is approved, the borrower initiates pickup (creates the
     * handover and notifies the lender), while the lender sees a
     * waiting state.
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
            $handover = $this->initiatePickup($id, $userId);
        }

        $isBorrower = (int) $handover['borrower_id'] === $userId;
        $isLender   = (int) $handover['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        if ($handover['handover_type'] === 'pickup') {
            $deposit = Deposit::findByBorrowId($id);

            if ($deposit !== null && $deposit['deposit_status'] === 'pending') {
                $_SESSION['deposit_errors'] = ['You must pay the security deposit before pickup.'];
                $this->redirect('/payments/deposit/' . $deposit['id_sdp']);
            }
        }

        $isVerifier = (int) $handover['generator_id'] !== $userId;

        $this->render('handover/verify', [
            'title'           => 'Verify Handover — NeighborhoodTools',
            'description'     => 'Enter the handover verification code to confirm tool ' . $handover['handover_type'] . '.',
            'pageCss'         => ['handover.css'],
            'handover'        => $handover,
            'isVerifier'      => $isVerifier,
            'handoverSuccess' => $this->flash('handover_success'),
            'handoverErrors'  => $this->flash('handover_errors', []),
            'handoverOld'     => $this->flash('handover_old', []),
        ]);
    }

    /**
     * Handle the case where no pending handover exists for an approved borrow.
     *
     * Borrower: checks deposit, creates the handover, notifies the
     * lender with the pickup code, and returns the new handover row.
     * Lender: renders the "awaiting borrower" state and halts execution.
     *
     * @return array The pending_handover_v row (borrower path only)
     */
    private function initiatePickup(int $borrowId, int $userId): array
    {
        try {
            $borrow = Borrow::findById($borrowId);
        } catch (\Throwable $e) {
            error_log('HandoverController::initiatePickup borrow lookup — ' . $e->getMessage());
            $borrow = null;
        }

        if ($borrow === null || $borrow['borrow_status'] !== 'approved') {
            $this->abort(404);
        }

        $isBorrower = (int) $borrow['borrower_id'] === $userId;
        $isLender   = (int) $borrow['lender_id'] === $userId;

        if (!$isBorrower && !$isLender) {
            $this->abort(403);
        }

        if ($isLender) {
            $this->render('handover/verify', [
                'title'            => 'Awaiting Pickup — NeighborhoodTools',
                'description'      => 'Waiting for the borrower to initiate pickup.',
                'pageCss'          => ['handover.css'],
                'awaitingBorrower' => true,
                'borrow'           => $borrow,
            ]);
            exit;
        }

        $deposit = Deposit::findByBorrowId($borrowId);

        if ($deposit !== null && $deposit['deposit_status'] === 'pending') {
            $_SESSION['deposit_errors'] = ['You must pay the security deposit before pickup.'];
            $this->redirect('/payments/deposit/' . $deposit['id_sdp']);
        }

        try {
            $handover = Handover::findPendingByBorrowId($borrowId);
        } catch (\Throwable $e) {
            error_log('HandoverController::initiatePickup race-check — ' . $e->getMessage());
            $handover = null;
        }

        if ($handover !== null) {
            return $handover;
        }

        try {
            $handoverId = Handover::create(
                borrowId: $borrowId,
                generatorId: (int) $borrow['lender_id'],
                type: 'pickup',
            );
            $pickupCode = Handover::getCodeById($handoverId);
        } catch (\Throwable $e) {
            error_log('HandoverController::initiatePickup handover creation — ' . $e->getMessage());
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/borrower');
        }

        if ($pickupCode !== null) {
            $borrowerName = $_SESSION['user_first_name'] ?? 'The borrower';

            try {
                Notification::send(
                    accountId: (int) $borrow['lender_id'],
                    type: 'approval',
                    title: 'Pickup Verification Code',
                    body: $borrowerName . ' is ready to pick up '
                        . $borrow['tool_name_tol']
                        . '. Your verification code is: ' . $pickupCode
                        . '. Share this code at pickup to confirm the handover.',
                    relatedBorrowId: $borrowId,
                );
            } catch (\Throwable $e) {
                error_log('HandoverController::initiatePickup lender notification — ' . $e->getMessage());
            }
        }

        try {
            $handover = Handover::findPendingByBorrowId($borrowId);
        } catch (\Throwable $e) {
            error_log('HandoverController::initiatePickup re-fetch — ' . $e->getMessage());
            $handover = null;
        }

        if ($handover === null) {
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/borrower');
        }

        return $handover;
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

        $code           = trim($_POST['code'] ?? '');
        $conditionNotes = trim($_POST['condition_notes'] ?? '');

        if ($code === '') {
            $_SESSION['handover_errors'] = ['code' => 'Please enter the verification code.'];
            $_SESSION['handover_old'] = ['code' => $code, 'condition_notes' => $conditionNotes];
            $this->redirect('/handover/' . $id);
        }

        if (mb_strlen($conditionNotes) > 2000) {
            $_SESSION['handover_errors'] = ['code' => 'Condition notes must be 2,000 characters or fewer.'];
            $_SESSION['handover_old'] = ['code' => $code, 'condition_notes' => $conditionNotes];
            $this->redirect('/handover/' . $id);
        }

        if ($handover['code_status'] === 'EXPIRED') {
            $_SESSION['handover_errors'] = ['code' => 'This verification code has expired. Please ask for a new one.'];
            $this->redirect('/handover/' . $id);
        }

        $storedCode = strtoupper($handover['verification_code_hov']);
        $inputCode  = strtoupper($code);

        if (!hash_equals($storedCode, $inputCode)) {
            $_SESSION['handover_errors'] = ['code' => 'Incorrect verification code. Please try again.'];
            $_SESSION['handover_old'] = ['code' => $code, 'condition_notes' => $conditionNotes];
            $this->redirect('/handover/' . $id);
        }

        $existingNotes = $handover['condition_notes_hov'] ?? '';
        $combinedNotes = null;

        if ($conditionNotes !== '') {
            $combinedNotes = ($existingNotes !== '')
                ? $existingNotes . "\n---\n" . $conditionNotes
                : $conditionNotes;
        }

        try {
            $verified = Handover::markVerified(
                handoverId: (int) $handover['id_hov'],
                verifierId: $userId,
                conditionNotes: $combinedNotes,
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

        if ($isPickup) {
            try {
                $deposit = Deposit::findHeldByBorrowId($id);

                if ($deposit !== null
                    && $deposit['payment_provider'] === 'stripe'
                    && !empty($deposit['external_payment_id_sdp'])
                ) {
                    $stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);
                    $stripe->paymentIntents->capture($deposit['external_payment_id_sdp']);
                }
            } catch (\Throwable $e) {
                error_log('HandoverController::confirm stripe capture — ' . $e->getMessage());
            }

            try {
                $borrow = Borrow::findById($id);

                if ($borrow !== null && (float) $borrow['rental_fee_tol'] > 0) {
                    $days      = (int) ceil((int) $borrow['loan_duration_hours_bor'] / 24);
                    $totalFee  = bcmul($borrow['rental_fee_tol'], (string) max($days, 1), 2);
                    $providerId = Deposit::getProviderIdByName('stripe');

                    if ($providerId !== null) {
                        Deposit::createTransaction(
                            depositId: null,
                            borrowId: $id,
                            providerId: $providerId,
                            type: 'rental_fee',
                            amount: $totalFee,
                            externalId: 'fee_' . uniqid('', true),
                            externalStatus: 'pending',
                            fromAccountId: (int) $borrow['borrower_id'],
                            toAccountId: (int) $borrow['lender_id'],
                        );
                    }
                }
            } catch (\Throwable $e) {
                error_log('HandoverController::confirm rental fee — ' . $e->getMessage());
            }
        } else {
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

        $_SESSION['handover_success'] = ucfirst($typeLabel) . ' confirmed! The ' . $toolName . ' handover is complete.';
        $this->redirect('/handover/' . $id);
    }
}
