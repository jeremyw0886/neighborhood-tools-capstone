<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Borrow;
use App\Models\Deposit;
use App\Models\Handover;
use App\Models\Notification;
use App\Models\Waiver;

/**
 * Pickup and return handover code generation + verification.
 *
 * Three actions: `verify` (GET — show the code, the entry form, or the
 * awaiting-counterparty state), `generate` (POST — mints a six-character
 * code with 24h expiry, after re-verifying waiver/deposit preconditions),
 * and `confirm` (POST — the counterparty enters the code to record the
 * pickup or return). Both POST paths are CSRF-validated and party-membership
 * checked before any state change.
 */
class HandoverController extends BaseController
{
    /**
     * Display the handover verification page for a borrow.
     *
     * Pure-read: renders the existing handover code, the verifier
     * code-entry form, an "awaiting other party" message, or a
     * "Generate code" button — but never creates a handover row.
     * Code creation is POST /handover/{id}/generate.
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
            $this->renderPreHandover($id, $userId);
            return;
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
     * Generate a pickup or return handover code for the current borrow.
     *
     * POST /handover/{id}/generate. Re-validates party membership,
     * waiver and deposit prerequisites, and the borrow's status before
     * calling Handover::create. Notifies the counterparty and redirects
     * back to GET /handover/{id} so the freshly-created code displays.
     */
    public function generate(string $borrowId): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $borrow = Borrow::findById($id);
        } catch (\Throwable $e) {
            error_log('HandoverController::generate borrow lookup — ' . $e->getMessage());
            $borrow = null;
        }

        if ($borrow === null) {
            $this->abort(404);
        }

        $type = match ($borrow['borrow_status']) {
            'approved' => 'pickup',
            'borrowed' => 'return',
            default    => null,
        };

        if ($type === null) {
            $this->redirect('/dashboard/loan/' . $id);
        }

        $generatorId = $type === 'pickup'
            ? (int) $borrow['lender_id']
            : (int) $borrow['borrower_id'];

        if ($generatorId !== $userId) {
            $this->abort(403);
        }

        try {
            $existing = Handover::findPendingByBorrowId($id);
        } catch (\Throwable $e) {
            error_log('HandoverController::generate race-check — ' . $e->getMessage());
            $existing = null;
        }

        if ($existing !== null && $existing['code_status'] !== 'EXPIRED') {
            $this->redirect('/handover/' . $id);
        }

        if ($type === 'pickup') {
            if (!Waiver::hasSignedWaiver($id)) {
                $_SESSION['handover_errors'] = ['general' => 'The borrower must sign the waiver before a pickup code can be generated.'];
                $this->redirect('/handover/' . $id);
            }

            $deposit = Deposit::findByBorrowId($id);

            if ($deposit !== null && $deposit['deposit_status'] === 'pending') {
                $_SESSION['handover_errors'] = ['general' => 'The borrower must pay the security deposit before a pickup code can be generated.'];
                $this->redirect('/handover/' . $id);
            }
        }

        try {
            Handover::create(
                borrowId: $id,
                generatorId: $generatorId,
                type: $type,
            );
        } catch (\Throwable $e) {
            error_log('HandoverController::generate handover creation — ' . $e->getMessage());
            $_SESSION['handover_errors'] = ['general' => 'Something went wrong. Please try again.'];
            $this->redirect('/handover/' . $id);
        }

        $generatorName = $_SESSION['user_first_name']
            ?? ($type === 'pickup' ? 'The lender' : 'The borrower');
        $recipientId   = $type === 'pickup'
            ? (int) $borrow['borrower_id']
            : (int) $borrow['lender_id'];

        try {
            Notification::send(
                accountId: $recipientId,
                type: $type === 'pickup' ? 'approval' : 'return',
                title: $type === 'pickup' ? 'Pickup Code Generated' : 'Return Code Generated',
                body: $generatorName . ' has generated a ' . $type . ' code for '
                    . $borrow['tool_name_tol']
                    . '. Meet up with them to receive the code and confirm the ' . $type . '.',
                relatedBorrowId: $id,
            );
        } catch (\Throwable $e) {
            error_log('HandoverController::generate notification — ' . $e->getMessage());
        }

        $this->redirect('/handover/' . $id);
    }

    /**
     * Render the pre-handover view (awaiting / generate-button) when
     * no pending handover exists. Pure-read; never mutates state.
     */
    private function renderPreHandover(int $borrowId, int $userId): void
    {
        try {
            $borrow = Borrow::findById($borrowId);
        } catch (\Throwable $e) {
            error_log('HandoverController::renderPreHandover borrow lookup — ' . $e->getMessage());
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

        match ($borrow['borrow_status']) {
            'approved' => $this->renderPickupPre($borrow, $isLender),
            'borrowed' => $this->renderReturnPre($borrow, $isBorrower),
            'returned', 'denied', 'cancelled' => $this->redirect('/dashboard/loan/' . $borrowId),
            default    => $this->abort(404),
        };
    }

    /**
     * Render the pickup pre-handover state (awaiting waiver / deposit /
     * lender, or a generate-code form for the lender once prerequisites
     * are met). Borrower without a signed waiver is redirected to /waiver.
     */
    private function renderPickupPre(array $borrow, bool $isLender): void
    {
        $borrowId     = (int) $borrow['id_bor'];
        $waiverSigned = Waiver::hasSignedWaiver($borrowId);

        if (!$isLender) {
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
            return;
        }

        if (!$waiverSigned) {
            $this->render('handover/verify', [
                'title'         => 'Awaiting Waiver — NeighborhoodTools',
                'description'   => 'Waiting for the borrower to sign the borrow waiver.',
                'pageCss'       => ['features.css'],
                'waiverPending' => true,
                'borrow'        => $borrow,
            ]);
            return;
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
            return;
        }

        $this->render('handover/verify', [
            'title'          => 'Generate Pickup Code — NeighborhoodTools',
            'description'    => 'Generate a pickup verification code to share with the borrower.',
            'pageCss'        => ['features.css'],
            'canGenerate'    => true,
            'handoverType'   => 'pickup',
            'borrow'         => $borrow,
            'handoverErrors' => $this->flash('handover_errors', []),
        ]);
    }

    /**
     * Render the return pre-handover state (awaiting borrower, or a
     * generate-code form for the borrower).
     */
    private function renderReturnPre(array $borrow, bool $isBorrower): void
    {
        if (!$isBorrower) {
            $this->render('handover/verify', [
                'title'            => 'Awaiting Return — NeighborhoodTools',
                'description'      => 'Waiting for the borrower to generate the return code.',
                'pageCss'          => ['features.css'],
                'awaitingBorrower' => true,
                'borrow'           => $borrow,
            ]);
            return;
        }

        $this->render('handover/verify', [
            'title'          => 'Generate Return Code — NeighborhoodTools',
            'description'    => 'Generate a return verification code to share with the lender.',
            'pageCss'        => ['features.css'],
            'canGenerate'    => true,
            'handoverType'   => 'return',
            'borrow'         => $borrow,
            'handoverErrors' => $this->flash('handover_errors', []),
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
            $_SESSION['borrow_decision'] = [
                'message'   => 'Pickup confirmed!',
                'detail'    => $toolName . ' handover is complete',
                'nextUrl'   => '/dashboard/loan/' . $id,
                'nextLabel' => 'View active borrow',
                'stayLabel' => 'Stay on this page',
            ];
            $this->redirect('/dashboard/loan/' . $id);
        }

        $_SESSION['rating_success'] = 'Return confirmed! Rate your experience below.';
        $_SESSION['borrow_decision'] = [
            'message'   => 'Return confirmed!',
            'detail'    => $toolName . ' has been returned — rate your experience below',
            'nextUrl'   => '/rate/' . $id,
            'nextLabel' => 'Rate your experience',
            'stayLabel' => 'Skip for now',
        ];
        $this->redirect('/rate/' . $id);
    }
}
