<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
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

        if ($deposit === null) {
            $this->abort(404);
        }

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
        ]);
    }
}
