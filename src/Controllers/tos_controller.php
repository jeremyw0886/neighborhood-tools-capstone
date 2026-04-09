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
        $shared = $this->getSharedData();
        $requiresAcceptance = $shared['isLoggedIn'] && !$shared['tosAccepted'] && $shared['currentTos'] !== null;

        $this->render('tos/show', [
            'title'              => 'Terms of Service — NeighborhoodTools',
            'description'        => 'Read the current NeighborhoodTools Terms of Service.',
            'pageCss'            => ['pages.css'],
            'requiresAcceptance' => $requiresAcceptance,
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
            $this->abort(404);
        }

        $currentTos = Tos::getCurrent();

        // Only accept the active TOS version — reject stale form submissions
        if ($currentTos === null || (int) $currentTos['id_tos'] !== $tosId) {
            $this->abort(404);
        }

        $accountId = (int) $_SESSION['user_id'];
        $cacheKey  = '_tos_accepted_' . $tosId;
        $wasEnforced = empty($_SESSION[$cacheKey]);

        // Silently skip if already accepted (UNIQUE constraint would throw otherwise)
        if (Tos::hasUserAccepted(accountId: $accountId, tosId: $tosId)) {
            $_SESSION[$cacheKey] = true;
            $this->redirect($wasEnforced ? '/' : $this->resolveBackUrl());
        }

        try {
            Tos::recordAcceptance(accountId: $accountId, tosId: $tosId);
            $_SESSION[$cacheKey] = true;
        } catch (\Throwable $e) {
            error_log('TosController::accept — ' . $e->getMessage());
            $this->abort(500);
        }

        $this->redirect($wasEnforced ? '/' : $this->resolveBackUrl());
    }

    /**
     * Resolve a safe back URL from the HTTP referrer.
     */
    private function resolveBackUrl(): string
    {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $host     = $_SERVER['HTTP_HOST'] ?? '';

        if ($referrer !== '' && $host !== '') {
            $parsed  = parse_url($referrer);
            $refHost = $parsed['host'] ?? '';

            if ($refHost === $host) {
                return $parsed['path'] ?? '/tos';
            }
        }

        return '/tos';
    }
}
