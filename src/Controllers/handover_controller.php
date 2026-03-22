<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Borrow;
use App\Models\Deposit;
use App\Models\Handover;
use App\Models\Notification;
use App\Models\Waiver;

class HandoverController extends BaseController
{
    /**
     * Display the handover verification page for a borrow.
     *
     * Dispatches to the appropriate flow based on borrow status:
     * approved → pickup (lender initiates), borrowed → return
     * (borrower initiates). If a pending handover already exists,
     * renders the code or code-entry form directly.
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

        if ($handover !== null && $handover['code_status'] === 'EXPIRED') {
            try {
                Handover::expireHandover((int) $handover['id_hov']);
            } catch (\Throwable $e) {
                error_log('HandoverController::verify expire — ' . $e->getMessage());
            }
            $handover = null;
        }

        if ($handover === null) {
            try {
                $borrow = Borrow::findById($id);
            } catch (\Throwable $e) {
                error_log('HandoverController::verify borrow lookup — ' . $e->getMessage());
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

            $handover = match ($borrow['borrow_status']) {
                'approved' => $this->initiatePickup($borrow, $userId),
                'borrowed' => $this->initiateReturn($borrow, $userId),
                default    => $this->abort(404),
            };
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
            'pageCss'         => ['features.css'],
            'handover'        => $handover,
            'isVerifier'      => $isVerifier,
            'handoverSuccess' => $this->flash('handover_success'),
            'handoverErrors'  => $this->flash('handover_errors', []),
            'handoverOld'     => $this->flash('handover_old', []),
        ]);
    }

    /**
     * Handle pickup initiation when borrow is approved and no handover exists.
     *
     * Lender: checks deposit status, creates the handover, notifies the
     * borrower to meet up, and returns the new handover row.
     * Borrower: renders the "awaiting lender" state and halts execution.
     *
     * @return array The pending_handover_v row (lender path only)
     */
    private function initiatePickup(array $borrow, int $userId): array
    {
        $borrowId = (int) $borrow['id_bor'];

        $waiverSigned = Waiver::hasSignedWaiver($borrowId);

        if ((int) $borrow['lender_id'] !== $userId) {
            if (!$waiverSigned) {
                $this->redirect('/waiver/' . $borrowId);
            }

            $this->render('handover/verify', [
                'title'          => 'Awaiting Pickup — NeighborhoodTools',
                'description'    => 'Waiting for the lender to generate the pickup code.',
                'pageCss'        => ['features.css'],
                'awaitingLender' => true,
                'borrow'         => $borrow,
            ]);
            exit;
        }

        if (!$waiverSigned) {
            $this->render('handover/verify', [
                'title'           => 'Awaiting Waiver — NeighborhoodTools',
                'description'     => 'Waiting for the borrower to sign the borrow waiver.',
                'pageCss'         => ['features.css'],
                'waiverPending'   => true,
                'borrow'          => $borrow,
            ]);
            exit;
        }

        $deposit = Deposit::findByBorrowId($borrowId);

        if ($deposit !== null && $deposit['deposit_status'] === 'pending') {
            $this->render('handover/verify', [
                'title'          => 'Awaiting Deposit — NeighborhoodTools',
                'description'    => 'Waiting for the borrower to pay the security deposit.',
                'pageCss'        => ['features.css'],
                'depositPending' => true,
                'borrow'         => $borrow,
            ]);
            exit;
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
        } catch (\Throwable $e) {
            error_log('HandoverController::initiatePickup handover creation — ' . $e->getMessage());
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/lender');
        }

        $lenderName = $_SESSION['user_first_name'] ?? 'The lender';

        try {
            Notification::send(
                accountId: (int) $borrow['borrower_id'],
                type: 'approval',
                title: 'Pickup Code Generated',
                body: $lenderName . ' has generated a pickup code for '
                    . $borrow['tool_name_tol']
                    . '. Meet up with them to receive the code and confirm the handover.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('HandoverController::initiatePickup borrower notification — ' . $e->getMessage());
        }

        try {
            $handover = Handover::findPendingByBorrowId($borrowId);
        } catch (\Throwable $e) {
            error_log('HandoverController::initiatePickup re-fetch — ' . $e->getMessage());
            $handover = null;
        }

        if ($handover === null) {
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/lender');
        }

        return $handover;
    }

    /**
     * Handle return initiation when borrow is borrowed and no handover exists.
     *
     * Borrower: creates the handover, notifies the lender to meet up,
     * and returns the new handover row.
     * Lender: renders the "awaiting borrower" state and halts execution.
     *
     * @return array The pending_handover_v row (borrower path only)
     */
    private function initiateReturn(array $borrow, int $userId): array
    {
        $borrowId = (int) $borrow['id_bor'];

        if ((int) $borrow['borrower_id'] !== $userId) {
            $this->render('handover/verify', [
                'title'            => 'Awaiting Return — NeighborhoodTools',
                'description'      => 'Waiting for the borrower to generate the return code.',
                'pageCss'          => ['features.css'],
                'awaitingBorrower' => true,
                'borrow'           => $borrow,
            ]);
            exit;
        }

        try {
            $handover = Handover::findPendingByBorrowId($borrowId);
        } catch (\Throwable $e) {
            error_log('HandoverController::initiateReturn race-check — ' . $e->getMessage());
            $handover = null;
        }

        if ($handover !== null) {
            return $handover;
        }

        try {
            $handoverId = Handover::create(
                borrowId: $borrowId,
                generatorId: (int) $borrow['borrower_id'],
                type: 'return',
            );
        } catch (\Throwable $e) {
            error_log('HandoverController::initiateReturn handover creation — ' . $e->getMessage());
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/dashboard/borrower');
        }

        $borrowerName = $_SESSION['user_first_name'] ?? 'The borrower';

        try {
            Notification::send(
                accountId: (int) $borrow['lender_id'],
                type: 'return',
                title: 'Return Code Generated',
                body: $borrowerName . ' has generated a return code for '
                    . $borrow['tool_name_tol']
                    . '. Meet up with them to receive the code and confirm the return.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('HandoverController::initiateReturn lender notification — ' . $e->getMessage());
        }

        try {
            $handover = Handover::findPendingByBorrowId($borrowId);
        } catch (\Throwable $e) {
            error_log('HandoverController::initiateReturn re-fetch — ' . $e->getMessage());
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

            if (!$isPickup) {
                $lenderId     = (int) $handover['lender_id'];
                $borrowerName = $handover['borrower_name'] ?? 'The borrower';
                $deposit      = Deposit::findByBorrowId($id);
                $body         = $deposit !== null
                    ? $borrowerName . ' has returned ' . $toolName . '. Your deposit hold has been released.'
                    : $borrowerName . ' has returned ' . $toolName . '.';

                Notification::send(
                    accountId: $lenderId,
                    type: 'return',
                    title: 'Tool Returned',
                    body: $body,
                    relatedBorrowId: $id,
                );
            }
        } catch (\Throwable $e) {
            error_log('HandoverController::confirm notification — ' . $e->getMessage());
        }

        if ($isPickup) {
            $_SESSION['handover_success'] = 'Pickup confirmed! The ' . $toolName . ' handover is complete.';
            $this->redirect('/dashboard/loan/' . $id);
        }

        $_SESSION['rating_success'] = 'Return confirmed! Rate your experience below.';
        $this->redirect('/rate/' . $id);
    }
}
