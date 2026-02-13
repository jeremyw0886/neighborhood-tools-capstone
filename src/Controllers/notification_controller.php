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
            'pageCss'       => ['notifications.css'],
            'notifications' => $notifications,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
        ]);
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
