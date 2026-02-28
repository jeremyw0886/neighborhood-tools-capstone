<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Borrow;
use App\Models\Dispute;
use App\Models\Notification;

class DisputeController extends BaseController
{
    private const int PER_PAGE                  = 12;
    private const array DISPUTES_SORT_FIELDS    = ['created_at_dsp', 'days_open', 'last_message_at', 'message_count'];
    private const array DISPUTES_ALLOWED_URGENCY = ['critical', 'high', 'moderate', 'new'];

    /**
     * List open disputes — paginated, sortable, with urgency filter.
     *
     * Accepts `?urgency`, `?sort`, `?dir` query params.
     */
    public function index(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $sortParams = $this->parseSortParams('', self::DISPUTES_SORT_FIELDS, 'created_at_dsp', 'DESC');

        $rawUrgency = $_GET['urgency'] ?? '';
        $urgency    = in_array($rawUrgency, self::DISPUTES_ALLOWED_URGENCY, true) ? $rawUrgency : null;

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Dispute::getFilteredCount($urgency);
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $disputes   = Dispute::getAll(
                limit:   self::PER_PAGE,
                offset:  $offset,
                sort:    $sortParams['sort'],
                dir:     $sortParams['dir'],
                urgency: $urgency,
            );
        } catch (\Throwable $e) {
            error_log('DisputeController::index — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $disputes   = [];
        }

        $filterParams = array_filter([
            'urgency' => $urgency,
            'sort'    => $sortParams['sort'],
            'dir'     => $sortParams['dir'],
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/disputes', [
            'title'        => 'Manage Disputes — NeighborhoodTools',
            'description'  => 'Review and resolve open disputes.',
            'pageCss'      => ['admin.css'],
            'disputes'     => $disputes,
            'totalCount'   => $totalCount,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => self::PER_PAGE,
            'urgency'      => $urgency,
            'sort'         => $sortParams['sort'],
            'dir'          => $sortParams['dir'],
            'filterParams' => $filterParams,
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
            'pageCss'     => ['features.css'],
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
        $isAdmin = Role::tryFrom($_SESSION['user_role'] ?? '')?->isAdmin() ?? false;

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

        $msgErrors = $_SESSION['dispute_message_errors'] ?? [];
        $msgOld    = $_SESSION['dispute_message_old'] ?? [];
        $msgSuccess = $_SESSION['dispute_message_success'] ?? '';
        unset(
            $_SESSION['dispute_message_errors'],
            $_SESSION['dispute_message_old'],
            $_SESSION['dispute_message_success'],
        );

        $this->render('disputes/show', [
            'title'       => htmlspecialchars($dispute['subject_text_dsp']) . ' — NeighborhoodTools',
            'description' => 'Dispute details and message thread.',
            'pageCss'     => ['features.css'],
            'dispute'     => $dispute,
            'messages'    => $messages,
            'isAdmin'     => $isAdmin,
            'msgErrors'   => $msgErrors,
            'msgOld'      => $msgOld,
            'msgSuccess'  => $msgSuccess,
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
            $this->abort(500);
        }

        try {
            Dispute::create($borrowId, $userId, $subject, $message);
        } catch (\Throwable $e) {
            error_log('DisputeController::store — ' . $e->getMessage());

            $_SESSION['dispute_errors'] = ['general' => 'Something went wrong filing your dispute. Please try again.'];
            $_SESSION['dispute_old']    = $oldInput;
            $this->redirect('/disputes/create/' . $borrowId);
        }

        $filerName   = $_SESSION['user_first_name'] ?? 'A user';
        $otherPartyId = $isBorrower ? (int) $borrow['lender_id'] : (int) $borrow['borrower_id'];

        try {
            Notification::send(
                accountId: $otherPartyId,
                type: 'request',
                title: 'Dispute Filed',
                body: $filerName . ' filed a dispute regarding ' . $borrow['tool_name_tol'] . ': ' . $subject,
                relatedBorrowId: $borrowId,
            );
        } catch (\Throwable $e) {
            error_log('DisputeController::store notification — ' . $e->getMessage());
        }

        $_SESSION['dispute_success'] = 'Your dispute has been filed. An admin will review it shortly.';
        $this->redirect('/dashboard');
    }

    private const array ADMIN_MESSAGE_TYPES = ['response', 'admin_note', 'resolution'];

    /**
     * Append a message to an open dispute's thread.
     *
     * Borrowers and lenders may post type "response". Admins may also
     * post "admin_note" (optionally internal) or "resolution". Only
     * open disputes accept new messages.
     */
    public function addMessage(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $disputeId = (int) $id;

        if ($disputeId < 1) {
            $this->abort(404);
        }

        $userId  = (int) $_SESSION['user_id'];
        $isAdmin = Role::tryFrom($_SESSION['user_role'] ?? '')?->isAdmin() ?? false;
        $message = trim($_POST['message'] ?? '');
        $redirectUrl = '/disputes/' . $disputeId;

        $errors = [];

        if ($message === '') {
            $errors['message'] = 'A message is required.';
        } elseif (mb_strlen($message) > 5000) {
            $errors['message'] = 'Message must be 5,000 characters or fewer.';
        }

        if ($errors !== []) {
            $_SESSION['dispute_message_errors'] = $errors;
            $_SESSION['dispute_message_old']    = ['message' => $message];
            $this->redirect($redirectUrl);
        }

        try {
            $dispute = Dispute::findByIdWithContext($disputeId);
        } catch (\Throwable $e) {
            error_log('DisputeController::addMessage dispute lookup — ' . $e->getMessage());
            $dispute = null;
        }

        if ($dispute === null) {
            $this->abort(404);
        }

        if ($dispute['dispute_status'] !== 'open') {
            $_SESSION['dispute_message_errors'] = ['general' => 'This dispute is no longer open.'];
            $this->redirect($redirectUrl);
        }

        $isBorrower = (int) $dispute['borrower_id'] === $userId;
        $isLender   = (int) $dispute['lender_id'] === $userId;

        if (!$isBorrower && !$isLender && !$isAdmin) {
            $this->abort(403);
        }

        $typeName   = 'response';
        $isInternal = false;

        if ($isAdmin) {
            $requestedType = trim($_POST['message_type'] ?? 'response');
            $typeName = in_array($requestedType, self::ADMIN_MESSAGE_TYPES, true)
                ? $requestedType
                : 'response';
            $isInternal = !empty($_POST['is_internal']);
        }

        try {
            Dispute::addMessage($disputeId, $userId, $typeName, $message, $isInternal);
        } catch (\Throwable $e) {
            error_log('DisputeController::addMessage — ' . $e->getMessage());

            $_SESSION['dispute_message_errors'] = ['general' => 'Something went wrong posting your message. Please try again.'];
            $_SESSION['dispute_message_old']    = ['message' => $message];
            $this->redirect($redirectUrl);
        }

        $borrowId    = (int) $dispute['id_bor_dsp'];
        $borrowerId  = (int) $dispute['borrower_id'];
        $lenderId    = (int) $dispute['lender_id'];
        $senderName  = $_SESSION['user_first_name'] ?? 'A user';
        $toolName    = $dispute['tool_name_tol'];

        if (!$isInternal) {
            $isResolution = $typeName === 'resolution';
            $title        = $isResolution ? 'Dispute Resolved' : 'New Dispute Message';
            $body         = $isResolution
                ? 'An admin resolved the dispute regarding ' . $toolName . '.'
                : $senderName . ' posted a message in the dispute regarding ' . $toolName . '.';

            $recipientIds = [];

            if ($isAdmin) {
                $recipientIds = [$borrowerId, $lenderId];
            } elseif ($isBorrower) {
                $recipientIds = [$lenderId];
            } elseif ($isLender) {
                $recipientIds = [$borrowerId];
            }

            foreach ($recipientIds as $recipientId) {
                try {
                    Notification::send(
                        accountId: $recipientId,
                        type: 'request',
                        title: $title,
                        body: $body,
                        relatedBorrowId: $borrowId,
                    );
                } catch (\Throwable $e) {
                    error_log('DisputeController::addMessage notification — ' . $e->getMessage());
                }
            }
        }

        $_SESSION['dispute_message_success'] = 'Message posted.';
        $this->redirect($redirectUrl);
    }
}
