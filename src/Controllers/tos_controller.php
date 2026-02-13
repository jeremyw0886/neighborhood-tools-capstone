<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Tos;

/**
 * Handles the Terms of Service standalone page and acceptance action.
 *
 * GET /tos         → show()   — standalone TOS page (fallback for the modal)
 * POST /tos/accept → accept() — record the user's acceptance
 */
class TosController extends BaseController
{
    /**
     * Render the standalone Terms of Service page.
     *
     * $currentTos and $tosAccepted are already available from getSharedData(),
     * but we pass explicit title/description/pageCss for this view.
     */
    public function show(): void
    {
        $this->render('tos/show', [
            'title'       => 'Terms of Service — NeighborhoodTools',
            'description' => 'Read the current NeighborhoodTools Terms of Service.',
            'pageCss'     => ['pages.css'],
        ]);
    }

    /**
     * Record the user's acceptance of the current TOS version.
     *
     * Validates CSRF, requires authentication, checks that the TOS ID
     * matches an active version, then records the acceptance with
     * IP and user-agent for the audit trail.
     *
     * Redirects back to the referrer (or /tos if no referrer).
     */
    public function accept(): void
    {
        $this->validateCsrf();
        $this->requireAuth();

        $tosId = (int) ($_POST['tos_id'] ?? 0);

        if ($tosId <= 0) {
            $this->abort(400);
        }

        $currentTos = Tos::getCurrent();

        // Only accept the active TOS version — reject stale form submissions
        if ($currentTos === null || (int) $currentTos['id_tos'] !== $tosId) {
            $this->abort(400);
        }

        $accountId = (int) $_SESSION['user_id'];

        // Silently skip if already accepted (UNIQUE constraint would throw otherwise)
        if (Tos::hasUserAccepted(accountId: $accountId, tosId: $tosId)) {
            $this->redirectBack();
        }

        try {
            Tos::recordAcceptance(accountId: $accountId, tosId: $tosId);
        } catch (\Throwable $e) {
            error_log('TosController::accept — ' . $e->getMessage());
            $this->abort(500);
        }

        $this->redirectBack();
    }

    /**
     * Redirect to the HTTP referrer, falling back to /tos.
     */
    private function redirectBack(): never
    {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';

        // Only redirect to same-origin referrers to prevent open redirects
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if ($referrer !== '' && str_contains($referrer, $host)) {
            $this->redirect($referrer);
        }

        $this->redirect('/tos');
    }
}
