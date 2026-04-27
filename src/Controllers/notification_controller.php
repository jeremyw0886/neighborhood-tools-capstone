<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Notification;

class NotificationController extends BaseController
{
    /** Results per page — divisible by 2, 3, and 4 for grid layouts. */
    private const int PER_PAGE = 12;

    private const array ALLOWED_FILTERS = ['unread', 'request', 'decision', 'activity'];

    /**
     * Show the user's notifications with pagination and optional filtering.
     *
     * Displays both read and unread notifications, newest first.
     * Accepts `?filter=` query param to narrow by status or type group.
     */
    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $rawFilter = $_GET['filter'] ?? '';
        $filter    = in_array($rawFilter, self::ALLOWED_FILTERS, true) ? $rawFilter : null;

        try {
            $notifications = Notification::getForUser(
                accountId: $userId,
                limit: self::PER_PAGE,
                offset: $offset,
                filter: $filter,
            );

            $totalCount = Notification::getCountForUser($userId, $filter);
        } catch (\Throwable $e) {
            error_log('NotificationController::index — ' . $e->getMessage());
            $notifications = [];
            $totalCount    = 0;
        }

        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $data = [
            'title'         => 'Notifications — NeighborhoodTools',
            'description'   => 'Your notifications and alerts.',
            'pageCss'       => ['pages.css'],
            'pageJs'        => ['notifications.js'],
            'notifications' => $notifications,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
            'filter'        => $filter,
        ];

        if ($this->isXhr()) {
            $this->renderPartial(BASE_PATH . '/src/Views/notifications/index.php', $data);
            return;
        }

        $this->render('notifications/index', $data);
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
     * Return the 5 most recent unread notifications as JSON for the bell dropdown.
     */
    public function preview(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        try {
            $unread = Notification::getUnreadForUser($userId, 5);
            $count  = Notification::getUnreadCount($userId);
        } catch (\Throwable $e) {
            error_log('NotificationController::preview — ' . $e->getMessage());
            $this->jsonResponse(500, ['success' => false, 'message' => 'Something went wrong.']);
        }

        $items = array_map(static fn(array $ntf): array => [
            'id'        => (int) $ntf['id_ntf'],
            'type'      => $ntf['notification_type'],
            'title'     => $ntf['title_ntf'],
            'body'      => $ntf['body_ntf'] ?? '',
            'timestamp' => $ntf['created_at_ntf'],
            'hoursAgo'  => (int) $ntf['hours_ago'],
            'toolName'  => $ntf['related_tool_name'] ?? null,
            'link'      => '/notifications/' . (int) $ntf['id_ntf'] . '/go',
        ], $unread);

        $this->jsonResponse(200, [
            'success' => true,
            'unread'  => $count,
            'items'   => $items,
        ]);
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
        $type       = $ntf['notification_type'] ?? '';
        $status     = $ntf['related_borrow_status'] ?? null;
        $borrowId   = $ntf['id_bor_ntf'] ?? null;
        $userId     = (int) $_SESSION['user_id'];
        $isLender   = $borrowId && ((int) ($ntf['related_lender_id'] ?? 0)) === $userId;

        if ($borrowId === null && $type === 'request') {
            return '/tools';
        }

        $loanUrl = $borrowId ? '/dashboard/loan/' . (int) $borrowId : null;

        return match ($type) {
            'request'  => ($loanUrl ?? '/dashboard/lender') . '#lifecycle-heading',
            'approval' => ($loanUrl ?? '/dashboard/borrower') . '#lifecycle-heading',
            'denial'   => $loanUrl ?? '/dashboard/borrower',
            'due'      => ($loanUrl ?? '/dashboard/borrower') . '#actions-heading',
            'return'   => match (true) {
                $status !== 'returned' && $loanUrl !== null => $loanUrl . '#handovers-heading',
                $status === 'returned' && $isLender && $loanUrl !== null => $loanUrl . '#lifecycle-heading',
                $status === 'returned' && !$isLender && $loanUrl !== null => $loanUrl . '#rate-heading',
                default => '/dashboard',
            },
            'rating'   => $loanUrl !== null
                ? $loanUrl . '#ratings-heading'
                : '/dashboard/history',
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

        $success = true;

        try {
            Notification::markRead(accountId: $userId, notificationIds: $notificationIds);
        } catch (\Throwable $e) {
            error_log('NotificationController::markRead — ' . $e->getMessage());
            $success = false;
        }

        if ($this->isXhr()) {
            $unread = 0;
            try {
                $unread = Notification::getUnreadCount($userId);
            } catch (\Throwable) {}

            $this->jsonResponse($success ? 200 : 500, [
                'success' => $success,
                'unread'  => $unread,
            ]);
        }

        $page      = max(1, (int) ($_POST['page'] ?? 1));
        $rawFilter = $_POST['filter'] ?? '';
        $filter    = in_array($rawFilter, self::ALLOWED_FILTERS, true) ? $rawFilter : null;

        $query = http_build_query(array_filter([
            'filter' => $filter,
            'page'   => $page > 1 ? $page : null,
        ]));

        $this->redirect('/notifications' . ($query !== '' ? '?' . $query : ''));
    }

    /**
     * Delete a single notification and redirect back to the same page.
     *
     * Clamps the page number when the deletion empties the current page.
     */
    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id     = (int) $id;
        $userId = (int) $_SESSION['user_id'];

        if ($id < 1) {
            $this->abort(404);
        }

        try {
            $deleted = Notification::delete($id, $userId);
        } catch (\Throwable $e) {
            error_log('NotificationController::delete — ' . $e->getMessage());
            $deleted = false;
        }

        if ($this->isXhr()) {
            $unread = 0;
            try {
                $unread = Notification::getUnreadCount($userId);
            } catch (\Throwable) {}

            $this->jsonResponse($deleted ? 200 : 500, [
                'success' => $deleted,
                'unread'  => $unread,
            ]);
        }

        $page      = max(1, (int) ($_POST['page'] ?? 1));
        $rawFilter = $_POST['filter'] ?? '';
        $filter    = in_array($rawFilter, self::ALLOWED_FILTERS, true) ? $rawFilter : null;

        $totalCount = Notification::getCountForUser($userId, $filter);
        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $query = http_build_query(array_filter([
            'filter' => $filter,
            'page'   => $page > 1 ? $page : null,
        ]));

        $this->redirect('/notifications' . ($query !== '' ? '?' . $query : ''));
    }

    /**
     * Delete all read notifications and redirect to page 1.
     */
    public function clearRead(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = (int) $_SESSION['user_id'];

        $success = true;

        try {
            $count = Notification::clearRead($userId);
        } catch (\Throwable $e) {
            error_log('NotificationController::clearRead — ' . $e->getMessage());
            $count   = 0;
            $success = false;
        }

        if ($this->isXhr()) {
            $unread = 0;
            try {
                $unread = Notification::getUnreadCount($userId);
            } catch (\Throwable) {}

            $this->jsonResponse($success ? 200 : 500, [
                'success' => $success,
                'cleared' => $count,
                'unread'  => $unread,
            ]);
        }

        $this->redirect('/notifications');
    }

    /**
     * Show the notification preferences page.
     */
    public function preferences(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];

        try {
            $prefs = Notification::getPreferences($userId);
        } catch (\Throwable $e) {
            error_log('NotificationController::preferences — ' . $e->getMessage());
            $prefs = ['due' => true, 'return' => true, 'rating' => true];
        }

        $this->render('notifications/preferences', [
            'title'       => 'Notification Preferences — NeighborhoodTools',
            'description' => 'Choose which notifications you receive.',
            'pageCss'     => ['pages.css'],
            'prefs'       => $prefs,
        ]);
    }

    /**
     * Save notification preferences and redirect back.
     */
    public function savePreferences(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = (int) $_SESSION['user_id'];

        $prefs = [
            'due'    => isset($_POST['pref_due']),
            'return' => isset($_POST['pref_return']),
            'rating' => isset($_POST['pref_rating']),
        ];

        try {
            Notification::updatePreferences($userId, $prefs);
            $_SESSION['pref_notice'] = 'Preferences saved.';
        } catch (\Throwable $e) {
            error_log('NotificationController::savePreferences — ' . $e->getMessage());
            $_SESSION['pref_notice'] = 'Something went wrong. Please try again.';
        }

        $this->redirect('/notifications/preferences');
    }
}
