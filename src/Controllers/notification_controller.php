<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Notification;

class NotificationController extends BaseController
{
    /** Results per page — divisible by 2, 3, and 4 for grid layouts. */
    private const int PER_PAGE = 12;

    /**
     * Show the user's notifications with pagination.
     *
     * Displays both read and unread notifications, newest first.
     * Pagination follows the same pattern as ToolController::index().
     */
    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        try {
            $notifications = Notification::getForUser(
                accountId: $userId,
                limit: self::PER_PAGE,
                offset: $offset,
            );

            $totalCount = Notification::getCountForUser($userId);
        } catch (\Throwable $e) {
            error_log('NotificationController::index — ' . $e->getMessage());
            $notifications = [];
            $totalCount    = 0;
        }

        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->render('notifications/index', [
            'title'         => 'Notifications — NeighborhoodTools',
            'description'   => 'Your notifications and alerts.',
            'pageCss'       => ['pages.css'],
            'notifications' => $notifications,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
        ]);
    }

    /**
     * Return the unread notification count as JSON.
     *
     * Lightweight polling endpoint — requires auth, rate-limited to prevent abuse.
     */
    public function unreadCount(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        try {
            $count = Notification::getUnreadCount($userId);
        } catch (\Throwable $e) {
            error_log('NotificationController::unreadCount — ' . $e->getMessage());
            $this->jsonResponse(500, ['success' => false, 'message' => 'Something went wrong.']);
        }

        $this->jsonResponse(200, ['success' => true, 'unread' => $count]);
    }

    /**
     * Look up a notification, mark it read, and redirect to the best destination.
     *
     * Destination is resolved from the notification type and current borrow
     * status so that stale notifications still route correctly.
     */
    public function redirectThrough(string $id): void
    {
        $this->requireAuth();

        $id     = (int) $id;
        $userId = (int) $_SESSION['user_id'];

        if ($id < 1) {
            $this->abort(404);
        }

        try {
            $ntf = Notification::getById($id, $userId);
        } catch (\Throwable $e) {
            error_log('NotificationController::redirectThrough — ' . $e->getMessage());
            $this->redirect('/notifications');
            return;
        }

        if ($ntf === null) {
            $this->abort(404);
        }

        if (!$ntf['is_read_ntf']) {
            try {
                Notification::markRead(accountId: $userId, notificationIds: (string) $id);
            } catch (\Throwable $e) {
                error_log('NotificationController::redirectThrough markRead — ' . $e->getMessage());
            }
        }

        $this->redirect($this->resolveDestination($ntf));
    }

    /**
     * Resolve the best destination URL for a notification.
     *
     * @param  array $ntf Notification row from getById()
     * @return string Destination path
     */
    private function resolveDestination(array $ntf): string
    {
        $type     = $ntf['notification_type'] ?? '';
        $status   = $ntf['related_borrow_status'] ?? null;
        $toolId   = $ntf['related_tool_id'] ?? null;
        $borrowId = $ntf['id_bor_ntf'] ?? null;

        return match ($type) {
            'request'  => $status === 'requested' && $toolId
                ? '/tools/' . (int) $toolId
                : '/dashboard/lender',
            'approval' => $toolId
                ? '/tools/' . (int) $toolId
                : '/dashboard/borrower',
            'denial'   => '/dashboard/borrower',
            'due'      => '/dashboard/borrower',
            'return'   => $status === 'returned' && $borrowId
                ? '/rate/' . (int) $borrowId
                : '/dashboard/lender',
            'rating'   => '/dashboard/history',
            default    => '/notifications',
        };
    }

    /**
     * Mark notifications as read and redirect back.
     *
     * Supports both "mark all" (no notification_ids posted) and selective
     * marking (comma-separated IDs in $_POST['notification_ids']).
     * The SP handles both cases via its p_notification_ids parameter.
     */
    public function markRead(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = (int) $_SESSION['user_id'];

        // Validate optional notification_ids — must be comma-separated integers
        $notificationIds = null;
        $rawIds = trim($_POST['notification_ids'] ?? '');

        if ($rawIds !== '') {
            $ids = array_map('trim', explode(',', $rawIds));

            // Every segment must be a positive integer
            $valid = array_filter($ids, static fn(string $v): bool =>
                ctype_digit($v) && (int) $v > 0
            );

            if (count($valid) === count($ids)) {
                $notificationIds = implode(',', $valid);
            }
            // If validation fails, silently fall through to mark-all (null)
        }

        try {
            Notification::markRead(accountId: $userId, notificationIds: $notificationIds);
        } catch (\Throwable $e) {
            error_log('NotificationController::markRead — ' . $e->getMessage());
        }

        // Redirect back to the notifications page (preserve current page number)
        $page = max(1, (int) ($_POST['page'] ?? 1));
        $this->redirect('/notifications' . ($page > 1 ? '?page=' . $page : ''));
    }
}
