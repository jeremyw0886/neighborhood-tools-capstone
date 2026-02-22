<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Borrow;
use App\Models\Incident;

class IncidentController extends BaseController
{
    /**
     * Display the incident report form for a borrow transaction.
     *
     * Any authenticated party (borrower or lender) to the borrow may
     * file an incident. The view shows the incident type selector
     * populated from incident_type_ity and context about the borrow.
     */
    public function create(string $borrowId): void
    {
        $this->requireAuth();

        $id = (int) $borrowId;

        if ($id < 1) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $borrow = Borrow::findById($id);
        } catch (\Throwable $e) {
            error_log('IncidentController::create borrow lookup — ' . $e->getMessage());
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

        try {
            $hasExisting = Incident::hasOpenIncident($id);
        } catch (\Throwable $e) {
            error_log('IncidentController::create incident check — ' . $e->getMessage());
            $hasExisting = false;
        }

        try {
            $incidentTypes = Incident::getTypes();
        } catch (\Throwable $e) {
            error_log('IncidentController::create types lookup — ' . $e->getMessage());
            $incidentTypes = [];
        }

        $errors = $_SESSION['incident_errors'] ?? [];
        $old    = $_SESSION['incident_old'] ?? [];
        unset($_SESSION['incident_errors'], $_SESSION['incident_old']);

        $this->render('incidents/create', [
            'title'         => 'Report an Incident — NeighborhoodTools',
            'description'   => 'File an incident report for a borrow transaction.',
            'pageCss'       => ['incident.css'],
            'borrow'        => $borrow,
            'hasExisting'   => $hasExisting,
            'incidentTypes' => $incidentTypes,
            'errors'        => $errors,
            'old'           => $old,
        ]);
    }
}
