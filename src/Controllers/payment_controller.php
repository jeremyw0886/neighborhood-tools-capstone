<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Deposit;

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
        $isAdmin = in_array($_SESSION['user_role'], ['admin', 'super_admin'], true);

        try {
            $deposit = Deposit::findById($depositId);
        } catch (\Throwable $e) {
            error_log('PaymentController::deposit — ' . $e->getMessage());
            $deposit = null;
        }

        if ($deposit !== null) {
            $isBorrower = (int) $deposit['borrower_id'] === $userId;
            $isLender   = (int) $deposit['lender_id'] === $userId;

            if (!$isBorrower && !$isLender && !$isAdmin) {
                $this->abort(403);
            }

            $this->render('payments/deposit', [
                'title'       => 'Security Deposit — NeighborhoodTools',
                'description' => 'View security deposit details and status.',
                'pageCss'     => ['payment.css'],
                'deposit'     => $deposit,
                'isAdmin'     => $isAdmin,
                'paymentMode' => false,
            ]);
            return;
        }

        $pending = Deposit::findPendingPayment($depositId);

        if ($pending === null) {
            $this->abort(404);
        }

        if ((int) $pending['borrower_id'] !== $userId && !$isAdmin) {
            $this->abort(403);
        }

        $viewData = [
            'title'                => 'Pay Security Deposit — NeighborhoodTools',
            'description'          => 'Complete your security deposit payment.',
            'pageCss'              => ['payment.css'],
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
                        'amount'                    => $amountCents,
                        'currency'                  => 'usd',
                        'capture_method'            => 'manual',
                        'metadata'                  => [
                            'deposit_id' => $depositId,
                            'account_id' => $userId,
                        ],
                        'automatic_payment_methods' => ['enabled' => true],
                    ]);

                    Deposit::storeExternalPaymentId($depositId, $paymentIntent->id);
                }

                $viewData['stripeClientSecret']   = $paymentIntent->client_secret;
                $viewData['stripePublishableKey'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];
                $viewData['cdnJs']                = ['https://js.stripe.com/v3/'];
            } catch (\Stripe\Exception\ApiErrorException $e) {
                error_log('PaymentController::deposit — Stripe API error: ' . $e->getMessage());
            } catch (\Throwable $e) {
                error_log('PaymentController::deposit — ' . $e->getMessage());
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

        $deposit = Deposit::findById($depositId);

        if ($deposit === null) {
            $raw = Deposit::findByIdRaw($depositId);

            if ($raw !== null) {
                $_SESSION['deposit_errors'] = ['This deposit has already been processed (' . $raw['deposit_status'] . ').'];
                header('Location: /admin');
                exit;
            }

            $this->abort(404);
        }

        $action = $_POST['action'] ?? '';

        if (!in_array($action, ['release', 'forfeit'], true)) {
            $_SESSION['deposit_errors'] = ['Invalid action.'];
            header('Location: /payments/deposit/' . $depositId);
            exit;
        }

        $providerId = Deposit::getProviderIdByName($deposit['payment_provider']);

        if ($providerId === null) {
            error_log('processDeposit — unknown provider: ' . $deposit['payment_provider']);
            $_SESSION['deposit_errors'] = ['Unable to process: payment provider not recognized.'];
            header('Location: /payments/deposit/' . $depositId);
            exit;
        }

        $externalStatus = $deposit['payment_provider'] === 'manual' ? 'completed' : 'simulated';

        if ($action === 'release') {
            $this->processRelease($depositId, $deposit, $providerId, $externalStatus);
        }

        $this->processForfeit($depositId, $deposit, $providerId, $externalStatus);
    }

    private function processRelease(int $depositId, array $deposit, int $providerId, string $externalStatus): void
    {
        try {
            $result = Deposit::release((int) $deposit['id_bor_sdp']);

            if (!$result['success']) {
                $_SESSION['deposit_errors'] = [$result['error'] ?? 'Release failed.'];
                header('Location: /payments/deposit/' . $depositId);
                exit;
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
            $_SESSION['deposit_success'] = 'Deposit of $' . $formatted . ' released to borrower.';
            header('Location: /admin');
            exit;
        } catch (\Throwable $e) {
            error_log('PaymentController::processDeposit(release) — ' . $e->getMessage());
            $_SESSION['deposit_errors'] = ['An unexpected error occurred. Please try again.'];
            header('Location: /payments/deposit/' . $depositId);
            exit;
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
            header('Location: /payments/deposit/' . $depositId);
            exit;
        }

        try {
            $result = Deposit::forfeit($depositId, $forfeitAmount, $reason, null);

            if (!$result['success']) {
                $_SESSION['deposit_errors'] = [$result['error'] ?? 'Forfeit failed.'];
                header('Location: /payments/deposit/' . $depositId);
                exit;
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

            $isPartial = bccomp($forfeitAmount, $deposit['amount_sdp'], 2) < 0;
            $partialMsg = '';

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
            $_SESSION['deposit_success'] = '$' . $formatted . ' forfeited to lender' . $partialMsg . '.';
            header('Location: /admin');
            exit;
        } catch (\Throwable $e) {
            error_log('PaymentController::processDeposit(forfeit) — ' . $e->getMessage());
            $_SESSION['deposit_errors'] = ['An unexpected error occurred. Please try again.'];
            header('Location: /payments/deposit/' . $depositId);
            exit;
        }
    }

    public function createStripeIntent(): void
    {
        $this->requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $this->jsonResponse(400, ['error' => 'Invalid request.']);
        }

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
                    'amount'                    => $amountCents,
                    'currency'                  => 'usd',
                    'capture_method'            => 'manual',
                    'metadata'                  => [
                        'deposit_id' => $depositId,
                        'account_id' => $_SESSION['user_id'],
                    ],
                    'automatic_payment_methods' => ['enabled' => true],
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

    public function history(): void
    {
        $this->requireAuth();

        $perPage    = 12;
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $offset     = ($page - 1) * $perPage;
        $userId     = (int) $_SESSION['user_id'];
        $isAdmin    = in_array($_SESSION['user_role'], ['admin', 'super_admin'], true);

        $totalCount = Deposit::getHistoryCount($userId, $isAdmin);
        $totalPages = (int) ceil($totalCount / $perPage) ?: 1;
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $transactions = Deposit::getHistory($userId, $isAdmin, $perPage, $offset);

        $this->render('payments/history', [
            'title'        => 'Payment History — NeighborhoodTools',
            'description'  => 'View your payment transaction history.',
            'pageCss'      => ['payment.css'],
            'transactions' => $transactions,
            'isAdmin'      => $isAdmin,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalCount'   => $totalCount,
        ]);
    }
}
