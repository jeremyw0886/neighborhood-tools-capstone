<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Deposit;
use App\Models\Notification;

class PaymentController extends BaseController
{
    public function deposit(string $id): void
    {
        $this->requireAuth();

        $depositId = (int) $id;

        if ($depositId < 1) {
            $this->abort(404);
        }

        $userId  = (int) $_SESSION['user_id'];
        $isAdmin = Role::tryFrom($_SESSION['user_role'] ?? '')?->isAdmin() ?? false;

        $pending = Deposit::findPendingPayment($depositId);

        if ($pending !== null) {
            $this->renderPaymentForm($pending, $depositId, $userId, $isAdmin);
            return;
        }

        $deposit = Deposit::findDetailById($depositId);

        if ($deposit !== null) {
            $this->renderDepositView($deposit, $userId, $isAdmin);
            return;
        }

        $this->abort(404);
    }

    private function renderDepositView(array $deposit, int $userId, bool $isAdmin): void
    {
        $isBorrower = (int) $deposit['borrower_id'] === $userId;
        $isLender   = (int) $deposit['lender_id'] === $userId;

        if (!$isBorrower && !$isLender && !$isAdmin) {
            $this->abort(403);
        }

        $isHeld  = strtolower($deposit['deposit_status']) === 'held';
        $pageJs  = $isAdmin && $isHeld ? ['deposit.js'] : [];

        $this->render('payments/deposit', [
            'title'          => 'Security Deposit — NeighborhoodTools',
            'description'    => 'View security deposit details and status.',
            'pageCss'        => ['features.css'],
            'pageJs'         => $pageJs,
            'deposit'        => $deposit,
            'isAdmin'        => $isAdmin,
            'isBorrower'     => $isBorrower,
            'isLender'       => $isLender,
            'paymentMode'    => false,
            'depositSuccess' => $this->flash('deposit_success'),
            'depositErrors'  => $this->flash('deposit_errors', []),
            'depositOld'     => $this->flash('deposit_old', []),
        ]);
    }

    private function renderPaymentForm(array $pending, int $depositId, int $userId, bool $isAdmin): void
    {
        if ((int) $pending['borrower_id'] !== $userId && !$isAdmin) {
            $this->abort(403);
        }

        $viewData = [
            'title'                => 'Pay Security Deposit — NeighborhoodTools',
            'description'          => 'Complete your security deposit payment.',
            'pageCss'              => ['features.css'],
            'pageJs'               => ['payment.js'],
            'deposit'              => $pending,
            'isAdmin'              => $isAdmin,
            'paymentMode'          => true,
            'stripeClientSecret'   => null,
            'stripePublishableKey' => null,
        ];

        if ($pending['payment_provider'] === 'stripe') {
            try {
                $stripe      = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);
                $amountCents = (int) bcmul($pending['amount_sdp'], '100', 0);

                if (!empty($pending['external_payment_id_sdp'])) {
                    $paymentIntent = $stripe->paymentIntents->retrieve(
                        $pending['external_payment_id_sdp']
                    );
                } else {
                    $paymentIntent = $stripe->paymentIntents->create([
                        'amount'               => $amountCents,
                        'currency'             => 'usd',
                        'capture_method'       => 'manual',
                        'payment_method_types' => ['card'],
                        'metadata'             => [
                            'deposit_id' => $depositId,
                            'account_id' => $userId,
                        ],
                    ]);

                    Deposit::storeExternalPaymentId($depositId, $paymentIntent->id);
                }

                $viewData['stripeClientSecret']   = $paymentIntent->client_secret;
                $viewData['stripePublishableKey'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];
                $viewData['cdnJs']                = ['https://js.stripe.com/v3/'];
            } catch (\Stripe\Exception\ApiErrorException $e) {
                error_log('PaymentController::renderPaymentForm — Stripe API error: ' . $e->getMessage());
            } catch (\Throwable $e) {
                error_log('PaymentController::renderPaymentForm — ' . $e->getMessage());
            }
        }

        $this->render('payments/deposit', $viewData);
    }

    public function processDeposit(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $depositId = (int) $id;

        if ($depositId < 1) {
            $this->abort(404);
        }

        $deposit = Deposit::findHeldById($depositId);

        if ($deposit === null) {
            $raw = Deposit::findByIdRaw($depositId);

            if ($raw !== null) {
                $_SESSION['deposit_errors'] = ['This deposit has already been processed (' . $raw['deposit_status'] . ').'];
                $this->redirect('/payments/deposit/' . $depositId);
            }

            $this->abort(404);
        }

        $action = $_POST['action'] ?? '';

        if (!in_array($action, ['release', 'forfeit'], true)) {
            $_SESSION['deposit_errors'] = ['Invalid action.'];
            $this->redirect('/payments/deposit/' . $depositId);
        }

        $providerId = Deposit::getProviderIdByName($deposit['payment_provider']);

        if ($providerId === null) {
            error_log('processDeposit — unknown provider: ' . $deposit['payment_provider']);
            $_SESSION['deposit_errors'] = ['Unable to process: payment provider not recognized.'];
            $this->redirect('/payments/deposit/' . $depositId);
        }

        $externalStatus = $deposit['payment_provider'] === 'manual' ? 'completed' : 'simulated';

        if ($action === 'release') {
            $borrowStatus = strtolower($deposit['borrow_status'] ?? '');

            if ($borrowStatus !== 'returned') {
                $_SESSION['deposit_errors'] = ['Cannot release deposit — the tool has not been returned yet (borrow status: ' . $borrowStatus . ').'];
                $this->redirect('/payments/deposit/' . $depositId);
            }

            $this->processRelease($depositId, $deposit, $providerId, $externalStatus);
        } else {
            $this->processForfeit($depositId, $deposit, $providerId, $externalStatus);
        }
    }

    private function processRelease(int $depositId, array $deposit, int $providerId, string $externalStatus): void
    {
        try {
            $result = Deposit::release((int) $deposit['id_bor_sdp']);

            if (!$result['success']) {
                $_SESSION['deposit_errors'] = [$result['error'] ?? 'Release failed.'];
                $this->redirect('/payments/deposit/' . $depositId);
            }

            $txResult = Deposit::createTransaction(
                $depositId,
                (int) $deposit['id_bor_sdp'],
                $providerId,
                'deposit_release',
                $deposit['amount_sdp'],
                'manual_release_' . uniqid('', true),
                $externalStatus,
                null,
                (int) $deposit['borrower_id'],
            );

            if ($txResult['success']) {
                Deposit::createTransactionMeta($txResult['id'], 'processed_by', (string) $_SESSION['user_id']);
            } else {
                error_log('processDeposit — transaction record failed after release: ' . $txResult['error']);
            }

            $formatted = number_format((float) $deposit['amount_sdp'], 2);
            $toolName  = $deposit['tool_name_tol'] ?? 'a tool';
            $borrowId  = (int) $deposit['id_bor_sdp'];

            try {
                Notification::send(
                    accountId: (int) $deposit['borrower_id'],
                    type: 'approval',
                    title: 'Deposit Released',
                    body: 'Your security deposit of $' . $formatted . ' for ' . $toolName . ' has been released.',
                    relatedBorrowId: $borrowId,
                );
            } catch (\Throwable $e) {
                error_log('PaymentController::processRelease notification — ' . $e->getMessage());
            }

            try {
                Notification::send(
                    accountId: (int) $deposit['lender_id'],
                    type: 'approval',
                    title: 'Deposit Released',
                    body: 'The security deposit of $' . $formatted . ' for ' . $toolName . ' has been released to the borrower.',
                    relatedBorrowId: $borrowId,
                );
            } catch (\Throwable $e) {
                error_log('PaymentController::processRelease lender notification — ' . $e->getMessage());
            }

            $_SESSION['deposit_success'] = 'Deposit of $' . $formatted . ' released to borrower.';
            $this->redirect('/admin');
        } catch (\Throwable $e) {
            error_log('PaymentController::processDeposit(release) — ' . $e->getMessage());
            $_SESSION['deposit_errors'] = ['An unexpected error occurred. Please try again.'];
            $this->redirect('/payments/deposit/' . $depositId);
        }
    }

    private function processForfeit(int $depositId, array $deposit, int $providerId, string $externalStatus): void
    {
        $forfeitAmount = $_POST['forfeit_amount'] ?? '';
        $reason        = trim($_POST['reason'] ?? '');
        $errors        = [];

        if ($forfeitAmount === '' || !preg_match('/^\d{1,6}(\.\d{1,2})?$/', $forfeitAmount)) {
            $errors['forfeit_amount'] = 'Enter a valid dollar amount.';
        } else {
            $depositAmount = $deposit['amount_sdp'];
            if (bccomp($forfeitAmount, '0.01', 2) < 0) {
                $errors['forfeit_amount'] = 'Amount must be at least $0.01.';
            } elseif (bccomp($forfeitAmount, $depositAmount, 2) > 0) {
                $errors['forfeit_amount'] = 'Amount cannot exceed the deposit ($' . number_format((float) $depositAmount, 2) . ').';
            }
        }

        if ($reason === '') {
            $errors['reason'] = 'A reason is required for forfeiture.';
        } elseif (mb_strlen($reason) > 2000) {
            $errors['reason'] = 'Reason must be 2000 characters or fewer.';
        }

        if ($errors) {
            $_SESSION['deposit_errors'] = $errors;
            $_SESSION['deposit_old']    = ['forfeit_amount' => $forfeitAmount, 'reason' => $reason];
            $this->redirect('/payments/deposit/' . $depositId);
        }

        try {
            $result = Deposit::forfeit($depositId, $forfeitAmount, $reason, null);

            if (!$result['success']) {
                $_SESSION['deposit_errors'] = [$result['error'] ?? 'Forfeit failed.'];
                $this->redirect('/payments/deposit/' . $depositId);
            }

            $txResult = Deposit::createTransaction(
                $depositId,
                (int) $deposit['id_bor_sdp'],
                $providerId,
                'deposit_forfeit',
                $forfeitAmount,
                'manual_forfeit_' . uniqid('', true),
                $externalStatus,
                (int) $deposit['borrower_id'],
                (int) $deposit['lender_id'],
            );

            if ($txResult['success']) {
                Deposit::createTransactionMeta($txResult['id'], 'processed_by', (string) $_SESSION['user_id']);
            } else {
                error_log('processDeposit — forfeit transaction record failed: ' . $txResult['error']);
            }

            $isPartial  = bccomp($forfeitAmount, $deposit['amount_sdp'], 2) < 0;
            $partialMsg = '';
            $remainder  = '0';

            if ($isPartial) {
                $remainder = bcsub($deposit['amount_sdp'], $forfeitAmount, 2);

                $releaseTx = Deposit::createTransaction(
                    $depositId,
                    (int) $deposit['id_bor_sdp'],
                    $providerId,
                    'deposit_release',
                    $remainder,
                    'manual_release_' . uniqid('', true),
                    $externalStatus,
                    null,
                    (int) $deposit['borrower_id'],
                );

                if ($releaseTx['success']) {
                    Deposit::createTransactionMeta($releaseTx['id'], 'processed_by', (string) $_SESSION['user_id']);
                }

                $partialMsg = ', $' . number_format((float) $remainder, 2) . ' released to borrower';
            }

            $formatted = number_format((float) $forfeitAmount, 2);
            $toolName  = $deposit['tool_name_tol'] ?? 'a tool';
            $borrowId  = (int) $deposit['id_bor_sdp'];

            $borrowerBody = $isPartial
                ? '$' . $formatted . ' of your security deposit for ' . $toolName . ' has been forfeited. $' . number_format((float) $remainder, 2) . ' has been released.'
                : 'Your security deposit of $' . $formatted . ' for ' . $toolName . ' has been forfeited.';

            try {
                Notification::send(
                    accountId: (int) $deposit['borrower_id'],
                    type: 'approval',
                    title: 'Deposit Forfeited',
                    body: $borrowerBody,
                    relatedBorrowId: $borrowId,
                );
            } catch (\Throwable $e) {
                error_log('PaymentController::processForfeit borrower notification — ' . $e->getMessage());
            }

            try {
                Notification::send(
                    accountId: (int) $deposit['lender_id'],
                    type: 'approval',
                    title: 'Deposit Forfeited',
                    body: '$' . $formatted . ' from a security deposit for ' . $toolName . ' has been forfeited in your favor.',
                    relatedBorrowId: $borrowId,
                );
            } catch (\Throwable $e) {
                error_log('PaymentController::processForfeit lender notification — ' . $e->getMessage());
            }

            $_SESSION['deposit_success'] = '$' . $formatted . ' forfeited to lender' . $partialMsg . '.';
            $this->redirect('/admin');
        } catch (\Throwable $e) {
            error_log('PaymentController::processDeposit(forfeit) — ' . $e->getMessage());
            $_SESSION['deposit_errors'] = ['An unexpected error occurred. Please try again.'];
            $this->redirect('/payments/deposit/' . $depositId);
        }
    }

    public function createStripeIntent(): void
    {
        $this->requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $this->jsonResponse(400, ['error' => 'Invalid request.']);
        }

        // Manual CSRF check — validateCsrf() reads $_POST/headers only and
        // redirects on failure; this XHR endpoint accepts the token from the
        // JSON body and must respond with a 403 envelope, not a redirect.
        $posted  = $input['csrf_token'] ?? '';
        $session = $_SESSION['csrf_token'] ?? '';

        if ($session === '' || !hash_equals($session, $posted)) {
            $this->jsonResponse(403, ['error' => 'Invalid security token.']);
        }

        $depositId = (int) ($input['deposit_id'] ?? 0);

        if ($depositId < 1) {
            $this->jsonResponse(400, ['error' => 'Invalid deposit ID.']);
        }

        $deposit = Deposit::findPendingPayment($depositId);

        if ($deposit === null) {
            $this->jsonResponse(404, ['error' => 'Deposit not found or already processed.']);
        }

        if ((int) $deposit['borrower_id'] !== (int) $_SESSION['user_id']) {
            $this->jsonResponse(403, ['error' => 'Unauthorized.']);
        }

        if ($deposit['payment_provider'] !== 'stripe') {
            $this->jsonResponse(400, ['error' => 'This deposit does not use Stripe.']);
        }

        try {
            $stripe      = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);
            $amountCents = (int) bcmul($deposit['amount_sdp'], '100', 0);

            if (!empty($deposit['external_payment_id_sdp'])) {
                $paymentIntent = $stripe->paymentIntents->retrieve(
                    $deposit['external_payment_id_sdp']
                );
            } else {
                $paymentIntent = $stripe->paymentIntents->create([
                    'amount'               => $amountCents,
                    'currency'             => 'usd',
                    'capture_method'       => 'manual',
                    'payment_method_types' => ['card'],
                    'metadata'             => [
                        'deposit_id' => $depositId,
                        'account_id' => $_SESSION['user_id'],
                    ],
                ]);

                Deposit::storeExternalPaymentId($depositId, $paymentIntent->id);
            }

            $this->jsonResponse(200, [
                'clientSecret'   => $paymentIntent->client_secret,
                'publishableKey' => $_ENV['STRIPE_PUBLISHABLE_KEY'],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('createStripeIntent — Stripe API error: ' . $e->getMessage());
            $this->jsonResponse(500, ['error' => 'Payment service unavailable. Please try again.']);
        } catch (\Throwable $e) {
            error_log('createStripeIntent — ' . $e->getMessage());
            $this->jsonResponse(500, ['error' => 'An unexpected error occurred.']);
        }
    }

    public function stripeWebhook(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAllowedStripeWebhookSource()) {
            error_log('stripeWebhook — rejected non-allowlisted source IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden.']);
            exit;
        }

        $payload   = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );
        } catch (\Throwable $e) {
            error_log('stripeWebhook — verification failed: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => 'Signature verification failed.']);
            exit;
        }

        try {
            match ($event->type) {
                'payment_intent.amount_capturable_updated' => $this->handlePaymentAuthorized($event->data->object),
                'payment_intent.succeeded'                 => $this->handlePaymentSucceeded($event->data->object),
                'payment_intent.payment_failed'            => $this->handlePaymentFailed($event->data->object),
                default                                    => error_log("stripeWebhook — unhandled event: {$event->type}"),
            };
        } catch (\Throwable $e) {
            error_log('stripeWebhook — handler error: ' . $e->getMessage());
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    private function handlePaymentAuthorized(\Stripe\PaymentIntent $paymentIntent): void
    {
        $depositId = (int) ($paymentIntent->metadata->deposit_id ?? 0);

        if ($depositId < 1) {
            error_log('handlePaymentAuthorized — missing deposit_id in metadata');
            return;
        }

        $deposit = Deposit::findPendingPayment($depositId);

        if ($deposit === null) {
            error_log("handlePaymentAuthorized — deposit {$depositId} not found or already processed");
            return;
        }

        try {
            Deposit::transitionToHeld($depositId, $paymentIntent->id);
            $this->notifyDepositHeld($deposit);
            error_log("handlePaymentAuthorized — deposit {$depositId} transitioned to held");
        } catch (\Throwable $e) {
            error_log('handlePaymentAuthorized — ' . $e->getMessage());
        }
    }

    private function handlePaymentSucceeded(\Stripe\PaymentIntent $paymentIntent): void
    {
        $depositId = (int) ($paymentIntent->metadata->deposit_id ?? 0);

        if ($depositId < 1) {
            error_log('handlePaymentSucceeded — missing deposit_id in metadata');
            return;
        }

        $deposit = Deposit::findPendingPayment($depositId);

        if ($deposit === null) {
            error_log("handlePaymentSucceeded — deposit {$depositId} not found or already processed");
            return;
        }

        try {
            Deposit::transitionToHeld($depositId, $paymentIntent->id);
            $this->notifyDepositHeld($deposit);
            error_log("handlePaymentSucceeded — deposit {$depositId} transitioned to held");
        } catch (\Throwable $e) {
            error_log('handlePaymentSucceeded — ' . $e->getMessage());
        }
    }

    private function handlePaymentFailed(\Stripe\PaymentIntent $paymentIntent): void
    {
        $depositId = (int) ($paymentIntent->metadata->deposit_id ?? 0);
        $reason    = $paymentIntent->last_payment_error?->message ?? 'Unknown failure';

        error_log("handlePaymentFailed — deposit {$depositId}: {$reason}");
    }

    public function complete(): void
    {
        $this->requireAuth();

        $paymentIntentId = $_GET['payment_intent'] ?? '';

        if ($paymentIntentId === '') {
            $this->abort(404);
        }

        try {
            $stripe        = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);
        } catch (\Throwable $e) {
            error_log('PaymentController::complete — Stripe API error: ' . $e->getMessage());
            $_SESSION['deposit_errors'] = ['Unable to verify payment status. Please contact support.'];
            $this->redirect('/dashboard');
        }

        $depositId = (int) ($paymentIntent->metadata->deposit_id ?? 0);

        if ($depositId < 1) {
            $this->abort(404);
        }

        $pending      = Deposit::findPendingPayment($depositId);
        $piStatus     = $paymentIntent->status;
        $isAuthorized = in_array($piStatus, ['requires_capture', 'succeeded'], true);

        if ($isAuthorized) {
            try {
                Deposit::transitionToHeld($depositId, $paymentIntent->id);

                if ($pending !== null) {
                    $this->notifyDepositHeld($pending);
                }
            } catch (\Throwable $e) {
                error_log('PaymentController::complete — ' . $e->getMessage());
            }

            $_SESSION['deposit_success'] = 'Payment confirmed. Your security deposit is now being held.';
            $borrowId = (int) ($pending['id_bor_sdp'] ?? 0);
            if ($borrowId > 0) {
                $this->redirect('/dashboard/loan/' . $borrowId);
            }
            $this->redirect('/payments/deposit/' . $depositId);
        }

        $_SESSION['deposit_errors'] = ['Payment could not be completed. Please try again.'];
        $this->redirect('/payments/deposit/' . $depositId);
    }

    public function history(): void
    {
        $this->requireAuth();

        $perPage    = 12;
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $offset     = ($page - 1) * $perPage;
        $userId     = (int) $_SESSION['user_id'];
        $isAdmin    = Role::tryFrom($_SESSION['user_role'] ?? '')?->isAdmin() ?? false;

        $totalCount = Deposit::getHistoryCount($userId, $isAdmin);
        $totalPages = (int) ceil($totalCount / $perPage) ?: 1;
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $transactions = Deposit::getHistory($userId, $isAdmin, $perPage, $offset);

        $this->render('payments/history', [
            'title'        => 'Payment History — NeighborhoodTools',
            'description'  => 'View your payment transaction history.',
            'pageCss'      => ['features.css'],
            'transactions' => $transactions,
            'isAdmin'      => $isAdmin,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalCount'   => $totalCount,
        ]);
    }

    /**
     * Notify borrower and lender that a security deposit is now held.
     */
    private function notifyDepositHeld(array $deposit): void
    {
        $toolName = $deposit['tool_name_tol'] ?? 'a tool';
        $amount   = number_format((float) $deposit['amount_sdp'], 2);
        $borrowId = (int) $deposit['id_bor_sdp'];

        try {
            Notification::send(
                accountId: (int) $deposit['lender_id'],
                type: 'approval',
                title: 'Deposit Held',
                body: 'A security deposit of $' . $amount . ' for ' . $toolName . ' is now being held.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('PaymentController::notifyDepositHeld lender — ' . $e->getMessage());
        }

        try {
            Notification::send(
                accountId: (int) $deposit['borrower_id'],
                type: 'approval',
                title: 'Deposit Confirmed',
                body: 'Your security deposit of $' . $amount . ' for ' . $toolName . ' has been confirmed and is being held.',
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('PaymentController::notifyDepositHeld borrower — ' . $e->getMessage());
        }
    }

    /**
     * Allow webhook traffic only from Stripe's published source IPs (or local dev).
     * Defense-in-depth in front of signature verification — short-circuits drive-by
     * probes before any cryptographic work runs.
     */
    private function isAllowedStripeWebhookSource(): bool
    {
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            return true;
        }

        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($remote === '127.0.0.1' || $remote === '::1') {
            return true;
        }

        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $clientIp  = trim(
            $forwarded !== '' ? explode(',', $forwarded, 2)[0] : $remote
        );

        if ($clientIp === '') {
            return false;
        }

        $allowed = require BASE_PATH . '/config/stripe-webhook-ips.php';

        return in_array($clientIp, $allowed, true);
    }
}
