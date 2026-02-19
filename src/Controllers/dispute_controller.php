<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Borrow;
use App\Models\Dispute;

class DisputeController extends BaseController
{
    private const int PER_PAGE = 12;

    /**
     * List open disputes with pagination.
     *
     * Admin-only — queries open_dispute_v for all open disputes
     * with reporter, borrower, lender details, message counts,
     * related incidents, and deposit information.
     */
    public function index(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        try {
            $disputes   = Dispute::getAll(limit: self::PER_PAGE, offset: $offset);
            $totalCount = Dispute::getCount();
        } catch (\Throwable $e) {
            error_log('DisputeController::index — ' . $e->getMessage());
            $disputes   = [];
            $totalCount = 0;
        }

        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->render('admin/disputes', [
            'title'       => 'Manage Disputes — NeighborhoodTools',
            'description' => 'Review and resolve open disputes.',
            'pageCss'     => ['admin.css'],
            'disputes'    => $disputes,
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
        ]);
    }

    /**
     * Display the dispute creation form for a borrow transaction.
     *
     * Any authenticated user who is a party (borrower or lender) to the
     * borrow may file a dispute. The DB trigger trg_dispute_before_insert
     * enforces this at the write layer; this method enforces it at the
     * read layer so unauthorized users never see the form.
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
            error_log('DisputeController::create borrow lookup — ' . $e->getMessage());
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
            $hasExisting = Dispute::hasOpenDispute($id);
        } catch (\Throwable $e) {
            error_log('DisputeController::create dispute check — ' . $e->getMessage());
            $hasExisting = false;
        }

        $errors = $_SESSION['dispute_errors'] ?? [];
        $old    = $_SESSION['dispute_old'] ?? [];
        unset($_SESSION['dispute_errors'], $_SESSION['dispute_old']);

        $this->render('disputes/create', [
            'title'       => 'File a Dispute — NeighborhoodTools',
            'description' => 'Report an issue with a borrow transaction.',
            'pageCss'     => ['dispute.css'],
            'borrow'      => $borrow,
            'hasExisting'  => $hasExisting,
            'errors'      => $errors,
            'old'         => $old,
        ]);
    }

    /**
     * Display a single dispute with its chronological message thread.
     *
     * Accessible to borrower, lender, and admins. Internal admin notes
     * (is_internal_dsm = true) are stripped for non-admin viewers.
     */
    public function show(string $id): void
    {
        $this->requireAuth();

        $disputeId = (int) $id;

        if ($disputeId < 1) {
            $this->abort(404);
        }

        $userId  = (int) $_SESSION['user_id'];
        $isAdmin = in_array($_SESSION['user_role'], ['admin', 'super_admin'], true);

        try {
            $dispute = Dispute::findByIdWithContext($disputeId);
        } catch (\Throwable $e) {
            error_log('DisputeController::show dispute lookup — ' . $e->getMessage());
            $dispute = null;
        }

        if ($dispute === null) {
            $this->abort(404);
        }

        $isBorrower = (int) $dispute['borrower_id'] === $userId;
        $isLender   = (int) $dispute['lender_id'] === $userId;

        if (!$isBorrower && !$isLender && !$isAdmin) {
            $this->abort(403);
        }

        try {
            $messages = Dispute::getMessages($disputeId);
        } catch (\Throwable $e) {
            error_log('DisputeController::show messages — ' . $e->getMessage());
            $messages = [];
        }

        if (!$isAdmin) {
            $messages = array_values(array_filter(
                $messages,
                static fn(array $msg): bool => !$msg['is_internal_dsm']
            ));
        }

        $this->render('disputes/show', [
            'title'       => htmlspecialchars($dispute['subject_text_dsp']) . ' — NeighborhoodTools',
            'description' => 'Dispute details and message thread.',
            'pageCss'     => ['dispute.css'],
            'dispute'     => $dispute,
            'messages'    => $messages,
            'isAdmin'     => $isAdmin,
        ]);
    }

    /**
     * Validate and persist a new dispute with its initial message.
     *
     * Expects POST fields: csrf_token, borrow_id, subject, message.
     * On success, redirects to the dashboard with a flash notice.
     * On validation failure, redirects back to the create form with
     * field-keyed errors and sticky input values.
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId   = (int) $_SESSION['user_id'];
        $borrowId = (int) ($_POST['borrow_id'] ?? 0);
        $subject  = trim($_POST['subject'] ?? '');
        $message  = trim($_POST['message'] ?? '');

        if ($borrowId < 1) {
            $this->abort(404);
        }

        $errors = [];

        if ($subject === '') {
            $errors['subject'] = 'A subject is required.';
        } elseif (mb_strlen($subject) > 255) {
            $errors['subject'] = 'Subject must be 255 characters or fewer.';
        }

        if ($message === '') {
            $errors['message'] = 'Please describe the issue.';
        } elseif (mb_strlen($message) > 5000) {
            $errors['message'] = 'Message must be 5,000 characters or fewer.';
        }

        $oldInput = ['subject' => $subject, 'message' => $message];

        if ($errors !== []) {
            $_SESSION['dispute_errors'] = $errors;
            $_SESSION['dispute_old']    = $oldInput;
            $this->redirect('/disputes/create/' . $borrowId);
        }

        try {
            $borrow = Borrow::findById($borrowId);
        } catch (\Throwable $e) {
            error_log('DisputeController::store borrow lookup — ' . $e->getMessage());
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
            if (Dispute::hasOpenDispute($borrowId)) {
                $_SESSION['dispute_errors'] = ['general' => 'An open dispute already exists for this transaction.'];
                $this->redirect('/disputes/create/' . $borrowId);
            }
        } catch (\Throwable $e) {
            error_log('DisputeController::store duplicate check — ' . $e->getMessage());
        }

        try {
            Dispute::create($borrowId, $userId, $subject, $message);

            $_SESSION['dispute_success'] = 'Your dispute has been filed. An admin will review it shortly.';
            $this->redirect('/dashboard');
        } catch (\Throwable $e) {
            error_log('DisputeController::store — ' . $e->getMessage());

            $_SESSION['dispute_errors'] = ['general' => 'Something went wrong filing your dispute. Please try again.'];
            $_SESSION['dispute_old']    = $oldInput;
            $this->redirect('/disputes/create/' . $borrowId);
        }
    }
}
