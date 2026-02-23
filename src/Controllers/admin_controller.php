<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Account;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Incident;
use App\Models\Neighborhood;
use App\Models\PlatformStats;
use App\Models\Tool;
use App\Models\Tos;
use App\Models\VectorImage;

class AdminController extends BaseController
{
    private const int PER_PAGE             = 12;
    private const array ALLOWED_RANGES     = [7, 14, 30];
    private const int DEFAULT_RANGE        = 14;

    /**
     * Admin dashboard — platform-wide summary stats and recent trends.
     *
     * Accepts `?range=7|14|30` to control the trends table lookback window.
     */
    public function dashboard(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $range = (int) ($_GET['range'] ?? self::DEFAULT_RANGE);

        if (!in_array($range, self::ALLOWED_RANGES, true)) {
            $range = self::DEFAULT_RANGE;
        }

        try {
            $stats = PlatformStats::getAdminDashboardCounts();
        } catch (\Throwable $e) {
            error_log('AdminController::dashboard — ' . $e->getMessage());
            $stats = [
                'totalMembers'    => 0,
                'activeMembers'   => 0,
                'availableTools'  => 0,
                'openDisputes'    => 0,
                'pendingDeposits' => 0,
                'openIncidents'   => 0,
                'upcomingEvents'  => 0,
            ];
        }

        try {
            $trends = PlatformStats::getRecentTrends($range);
        } catch (\Throwable $e) {
            error_log('AdminController::dashboard trends — ' . $e->getMessage());
            $trends = [];
        }

        $this->render('admin/dashboard', [
            'title'         => 'Admin Dashboard — NeighborhoodTools',
            'description'   => 'Platform administration overview and management.',
            'pageCss'       => ['dashboard.css', 'admin.css'],
            'stats'         => $stats,
            'trends'        => $trends,
            'range'         => $range,
            'allowedRanges' => self::ALLOWED_RANGES,
        ]);
    }

    /**
     * User management — paginated list of platform members.
     *
     * Queries user_reputation_fast_v for a paginated table with ratings
     * and activity summaries. Pending rows surface approve/deny actions.
     */
    public function users(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $flash = $_SESSION['admin_users_flash'] ?? null;
        unset($_SESSION['admin_users_flash']);

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Account::getActiveCount();
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $users      = Account::getAllForAdmin(self::PER_PAGE, $offset);
        } catch (\Throwable $e) {
            error_log('AdminController::users — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $users      = [];
        }

        $this->render('admin/users', [
            'title'       => 'Manage Users — NeighborhoodTools',
            'description' => 'View and manage platform members.',
            'pageCss'     => ['admin.css'],
            'users'       => $users,
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
            'flash'       => $flash,
        ]);
    }

    /**
     * Approve a pending account — sets status to 'active'.
     */
    public function approveUser(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $accountId = (int) $id;

        if ($accountId < 1) {
            $this->abort(404);
        }

        try {
            $user = Account::findById($accountId);
        } catch (\Throwable $e) {
            error_log('AdminController::approveUser — ' . $e->getMessage());
            $this->abort(500);
        }

        if ($user === null) {
            $this->abort(404);
        }

        if ($user['account_status'] !== 'pending') {
            $_SESSION['admin_users_flash'] = 'Account is not pending approval.';
            $this->redirect('/admin/users');
        }

        try {
            Account::updateStatus($accountId, 'active');
            $_SESSION['admin_users_flash'] = $user['full_name'] . ' has been approved.';
        } catch (\Throwable $e) {
            error_log('AdminController::approveUser — ' . $e->getMessage());
            $_SESSION['admin_users_flash'] = 'Failed to approve account.';
        }

        $this->redirect('/admin/users');
    }

    /**
     * Deny a pending account — sets status to 'suspended'.
     */
    public function denyUser(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $accountId = (int) $id;

        if ($accountId < 1) {
            $this->abort(404);
        }

        try {
            $user = Account::findById($accountId);
        } catch (\Throwable $e) {
            error_log('AdminController::denyUser — ' . $e->getMessage());
            $this->abort(500);
        }

        if ($user === null) {
            $this->abort(404);
        }

        if ($user['account_status'] !== 'pending') {
            $_SESSION['admin_users_flash'] = 'Account is not pending approval.';
            $this->redirect('/admin/users');
        }

        try {
            Account::updateStatus($accountId, 'suspended');
            $_SESSION['admin_users_flash'] = $user['full_name'] . ' has been denied.';
        } catch (\Throwable $e) {
            error_log('AdminController::denyUser — ' . $e->getMessage());
            $_SESSION['admin_users_flash'] = 'Failed to deny account.';
        }

        $this->redirect('/admin/users');
    }

    /**
     * Toggle a user's status between active and suspended.
     *
     * Permission hierarchy:
     *   - Admin: can toggle members only (not admins or super admins)
     *   - Super Admin: can toggle members and admins (not other super admins)
     *   - Nobody can modify their own account
     */
    public function updateUserStatus(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $accountId = (int) $id;

        if ($accountId < 1) {
            $this->abort(404);
        }

        try {
            $user = Account::findById($accountId);
        } catch (\Throwable $e) {
            error_log('AdminController::updateUserStatus — ' . $e->getMessage());
            $this->abort(500);
        }

        if ($user === null) {
            $this->abort(404);
        }

        $targetStatus = $user['account_status'];
        $targetRole   = $user['role_name_rol'];
        $actorRole    = Role::from($_SESSION['user_role']);
        $actorId      = (int) $_SESSION['user_id'];

        if ($actorId === $accountId) {
            $_SESSION['admin_users_flash'] = 'You cannot modify your own account status.';
            $this->redirect('/admin/users');
        }

        if (!in_array($targetStatus, ['active', 'suspended'], true)) {
            $_SESSION['admin_users_flash'] = 'Only active or suspended accounts can be toggled.';
            $this->redirect('/admin/users');
        }

        if ($targetRole === 'super_admin') {
            $_SESSION['admin_users_flash'] = 'Super admin accounts cannot be modified.';
            $this->redirect('/admin/users');
        }

        if ($actorRole === Role::Admin && $targetRole !== 'member') {
            $_SESSION['admin_users_flash'] = 'Admins can only manage member accounts.';
            $this->redirect('/admin/users');
        }

        $newStatus = $targetStatus === 'active' ? 'suspended' : 'active';
        $action    = $newStatus === 'suspended' ? 'suspended' : 'activated';

        try {
            Account::updateStatus($accountId, $newStatus);
            $_SESSION['admin_users_flash'] = $user['full_name'] . ' has been ' . $action . '.';
        } catch (\Throwable $e) {
            error_log('AdminController::updateUserStatus — ' . $e->getMessage());
            $_SESSION['admin_users_flash'] = 'Failed to update account status.';
        }

        $this->redirect('/admin/users');
    }

    /**
     * Tool management — paginated list of all tools with analytics.
     *
     * Queries tool_statistics_fast_v (materialized every 2 hours) for
     * borrow counts, ratings, incidents, and condition per tool.
     */
    public function tools(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Tool::getAdminCount();
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $tools      = Tool::getAdminList(self::PER_PAGE, $offset);
        } catch (\Throwable $e) {
            error_log('AdminController::tools — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $tools      = [];
        }

        $this->render('admin/tools', [
            'title'       => 'Manage Tools — NeighborhoodTools',
            'description' => 'View and manage listed tools.',
            'pageCss'     => ['admin.css'],
            'tools'       => $tools,
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
        ]);
    }

    /**
     * Event management — upcoming community events with timing filters.
     *
     * Queries upcoming_event_v with optional timing filter (HAPPENING NOW,
     * THIS WEEK, THIS MONTH, UPCOMING). Includes attendee counts from
     * EventAttendance for each listed event.
     */
    public function events(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $timing = trim($_GET['timing'] ?? '');
        $timing = $timing !== '' ? strtoupper($timing) : null;
        $page   = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $totalCount   = Event::getUpcomingCount($timing);
            $timingCounts = Event::getTimingCounts();
        } catch (\Throwable $e) {
            error_log('AdminController::events counts — ' . $e->getMessage());
            $totalCount   = 0;
            $timingCounts = [];
        }

        $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        try {
            $events = Event::getUpcoming(timing: $timing, limit: self::PER_PAGE, offset: $offset);
        } catch (\Throwable $e) {
            error_log('AdminController::events list — ' . $e->getMessage());
            $events = [];
        }

        $attendeeCounts = [];

        if ($events !== []) {
            try {
                $eventIds       = array_column($events, 'id_evt');
                $attendeeCounts = EventAttendance::getAttendeeCounts($eventIds);
            } catch (\Throwable $e) {
                error_log('AdminController::events attendance — ' . $e->getMessage());
            }
        }

        $filterParams = array_filter([
            'timing' => $timing,
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/events', [
            'title'          => 'Manage Events — NeighborhoodTools',
            'description'    => 'View and manage community events.',
            'pageCss'        => ['admin.css'],
            'events'         => $events,
            'totalCount'     => $totalCount,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'perPage'        => self::PER_PAGE,
            'timing'         => $timing,
            'timingCounts'   => $timingCounts,
            'attendeeCounts' => $attendeeCounts,
            'filterParams'   => $filterParams,
        ]);
    }

    /**
     * Incident management — paginated open incident reports.
     *
     * Queries open_incident_v for unresolved incidents with full context:
     * reporter, borrower, lender, tool, deposit, and related dispute counts.
     */
    public function incidents(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $page = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $totalCount = Incident::getOpenCount();
        } catch (\Throwable $e) {
            error_log('AdminController::incidents count — ' . $e->getMessage());
            $totalCount = 0;
        }

        $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        try {
            $incidents = Incident::getOpen(self::PER_PAGE, $offset);
        } catch (\Throwable $e) {
            error_log('AdminController::incidents list — ' . $e->getMessage());
            $incidents = [];
        }

        $this->render('admin/incidents', [
            'title'       => 'Manage Incidents — NeighborhoodTools',
            'description' => 'Review open incident reports.',
            'pageCss'     => ['admin.css'],
            'incidents'   => $incidents,
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
        ]);
    }

    /**
     * Reports — neighborhood statistics from materialized summaries.
     *
     * Displays a paginated table of per-neighborhood stats pulled from
     * neighborhood_summary_fast_v via the Neighborhood model.
     */
    public function reports(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        try {
            $page          = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount    = Neighborhood::getSummaryCount();
            $totalPages    = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page          = min($page, $totalPages);
            $offset        = ($page - 1) * self::PER_PAGE;
            $neighborhoods = Neighborhood::getSummaryList(self::PER_PAGE, $offset);
        } catch (\Throwable $e) {
            error_log('AdminController::reports — ' . $e->getMessage());
            $page          = 1;
            $totalCount    = 0;
            $totalPages    = 1;
            $neighborhoods = [];
        }

        $this->render('admin/reports', [
            'title'          => 'Reports — NeighborhoodTools',
            'description'    => 'Platform reports and statistics.',
            'pageCss'        => ['admin.css'],
            'neighborhoods'  => $neighborhoods,
            'totalCount'     => $totalCount,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'perPage'        => self::PER_PAGE,
        ]);
    }

    /**
     * Audit log — platform activity history.
     *
     * Stub — no audit log table exists in the schema yet.
     */
    public function auditLog(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $this->render('admin/audit-log', [
            'title'       => 'Audit Log — NeighborhoodTools',
            'description' => 'Platform activity and audit trail.',
            'pageCss'     => ['admin.css'],
        ]);
    }

    /**
     * TOS management — current version and non-compliant users.
     *
     * Displays the active TOS version summary alongside a paginated
     * list of active members who have not yet accepted it, pulled
     * from tos_acceptance_required_v via the Tos model.
     */
    public function tos(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Tos::getNonCompliantCount();
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $users      = Tos::getNonCompliantUsers(self::PER_PAGE, $offset);
        } catch (\Throwable $e) {
            error_log('AdminController::tos — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $users      = [];
        }

        $this->render('admin/tos', [
            'title'       => 'Manage Terms of Service — NeighborhoodTools',
            'description' => 'View and manage Terms of Service versions.',
            'pageCss'     => ['admin.css'],
            'users'       => $users,
            'totalCount'  => $totalCount,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => self::PER_PAGE,
        ]);
    }

    /**
     * Show the create-TOS-version form (Super Admin only).
     */
    public function showCreateTos(): void
    {
        $this->requireRole(Role::SuperAdmin);

        $errors = $_SESSION['tos_create_errors'] ?? [];
        $old    = $_SESSION['tos_create_old'] ?? [];
        unset($_SESSION['tos_create_errors'], $_SESSION['tos_create_old']);

        $this->render('admin/tos-create', [
            'title'       => 'Create TOS Version — NeighborhoodTools',
            'description' => 'Publish a new Terms of Service version.',
            'pageCss'     => ['admin.css'],
            'errors'      => $errors,
            'old'         => $old,
        ]);
    }

    /**
     * Process the create-TOS-version form (Super Admin only).
     */
    public function createTosVersion(): void
    {
        $this->requireRole(Role::SuperAdmin);
        $this->validateCsrf();

        $version     = trim($_POST['version'] ?? '');
        $title       = trim($_POST['title'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $summary     = trim($_POST['summary'] ?? '');
        $effectiveAt = trim($_POST['effective_at'] ?? '');

        $old = compact('version', 'title', 'content', 'summary', 'effectiveAt');

        $errors = [];

        if ($version === '' || mb_strlen($version) > 20) {
            $errors['version'] = 'Version is required (max 20 characters).';
        }

        if ($title === '' || mb_strlen($title) > 255) {
            $errors['title'] = 'Title is required (max 255 characters).';
        }

        if ($content === '') {
            $errors['content'] = 'Content is required.';
        }

        if ($effectiveAt === '') {
            $errors['effective_at'] = 'Effective date is required.';
        }

        if ($errors !== []) {
            $_SESSION['tos_create_errors'] = $errors;
            $_SESSION['tos_create_old']    = $old;
            $this->redirect('/admin/tos/create');
        }

        $effectiveTimestamp = $effectiveAt . ' 00:00:00';

        try {
            Tos::createVersion(
                version: $version,
                title: $title,
                content: $content,
                summary: $summary !== '' ? $summary : null,
                effectiveAt: $effectiveTimestamp,
                createdBy: (int) $_SESSION['user_id'],
            );
            $_SESSION['admin_tos_flash'] = 'TOS version ' . $version . ' created successfully.';
        } catch (\Throwable $e) {
            error_log('AdminController::createTosVersion — ' . $e->getMessage());

            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errors['version'] = 'This version already exists.';
                $_SESSION['tos_create_errors'] = $errors;
                $_SESSION['tos_create_old']    = $old;
                $this->redirect('/admin/tos/create');
            }

            $_SESSION['admin_tos_flash'] = 'Failed to create TOS version.';
        }

        $this->redirect('/admin/tos');
    }

    /**
     * Category management — icons and vector image library.
     */
    public function categories(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $flash = $_SESSION['admin_categories_flash'] ?? null;
        unset($_SESSION['admin_categories_flash']);

        try {
            $categories = Category::getAllWithIcons();
            $vectors    = VectorImage::getAll();
        } catch (\Throwable $e) {
            error_log('AdminController::categories — ' . $e->getMessage());
            $categories = [];
            $vectors    = [];
        }

        $this->render('admin/categories', [
            'title'       => 'Manage Categories — NeighborhoodTools',
            'description' => 'Manage tool categories and vector image icons.',
            'pageCss'     => ['admin.css'],
            'categories'  => $categories,
            'vectors'     => $vectors,
            'flash'       => $flash,
        ]);
    }

    public function uploadVector(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $file        = $_FILES['vector_file'] ?? null;
        $description = trim($_POST['description'] ?? '');

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['admin_categories_flash'] = 'No file uploaded or upload error occurred.';
            $this->redirect('/admin/categories');
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if ($mimeType !== 'image/svg+xml') {
            $_SESSION['admin_categories_flash'] = 'Only SVG files are allowed.';
            $this->redirect('/admin/categories');
        }

        if ($file['size'] > 1_048_576) {
            $_SESSION['admin_categories_flash'] = 'File must be under 1 MB.';
            $this->redirect('/admin/categories');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'svg') {
            $_SESSION['admin_categories_flash'] = 'File must have an .svg extension.';
            $this->redirect('/admin/categories');
        }

        $fileName = uniqid('vec_', true) . '.svg';
        $destPath = BASE_PATH . '/public/uploads/vectors/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $_SESSION['admin_categories_flash'] = 'Failed to save uploaded file.';
            $this->redirect('/admin/categories');
        }

        try {
            VectorImage::create(
                $fileName,
                $description !== '' ? $description : null,
                (int) $_SESSION['user_id']
            );
            $_SESSION['admin_categories_flash'] = 'Vector image uploaded successfully.';
        } catch (\Throwable $e) {
            error_log('AdminController::uploadVector — ' . $e->getMessage());
            @unlink($destPath);
            $_SESSION['admin_categories_flash'] = 'Failed to save vector image record.';
        }

        $this->redirect('/admin/categories');
    }

    /**
     * Global admin search — queries all entity models for a search term.
     *
     * @return void
     */
    public function search(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $term = trim($_GET['q'] ?? '');

        $results = [
            'users'         => [],
            'tools'         => [],
            'disputes'      => [],
            'events'        => [],
            'incidents'     => [],
            'neighborhoods' => [],
        ];

        if ($term !== '') {
            try {
                $results['users'] = Account::adminSearch($term);
            } catch (\Throwable $e) {
                error_log('AdminController::search users — ' . $e->getMessage());
            }

            try {
                $results['tools'] = Tool::adminSearch($term);
            } catch (\Throwable $e) {
                error_log('AdminController::search tools — ' . $e->getMessage());
            }

            try {
                $results['disputes'] = Dispute::adminSearch($term);
            } catch (\Throwable $e) {
                error_log('AdminController::search disputes — ' . $e->getMessage());
            }

            try {
                $results['events'] = Event::adminSearch($term);
            } catch (\Throwable $e) {
                error_log('AdminController::search events — ' . $e->getMessage());
            }

            try {
                $results['incidents'] = Incident::adminSearch($term);
            } catch (\Throwable $e) {
                error_log('AdminController::search incidents — ' . $e->getMessage());
            }

            try {
                $results['neighborhoods'] = Neighborhood::adminSearch($term);
            } catch (\Throwable $e) {
                error_log('AdminController::search neighborhoods — ' . $e->getMessage());
            }
        }

        if (($_GET['format'] ?? '') === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($results, JSON_THROW_ON_ERROR);
            exit;
        }

        $totalCount = array_sum(array_map('count', $results));

        $this->render('admin/search', [
            'title'       => 'Search Results — NeighborhoodTools',
            'description' => 'Admin search results.',
            'pageCss'     => ['admin.css'],
            'term'        => $term,
            'results'     => $results,
            'totalCount'  => $totalCount,
        ]);
    }

    public function assignCategoryIcon(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $categoryId = (int) $id;

        if ($categoryId < 1) {
            $this->abort(404);
        }

        $vectorId = trim($_POST['vector_id'] ?? '');
        $vectorId = $vectorId !== '' ? (int) $vectorId : null;

        if ($vectorId !== null && $vectorId < 1) {
            $_SESSION['admin_categories_flash'] = 'Invalid vector image selected.';
            $this->redirect('/admin/categories');
        }

        try {
            Category::updateIcon($categoryId, $vectorId);
            $_SESSION['admin_categories_flash'] = 'Category icon updated.';
        } catch (\Throwable $e) {
            error_log('AdminController::assignCategoryIcon — ' . $e->getMessage());
            $_SESSION['admin_categories_flash'] = 'Failed to update category icon.';
        }

        $this->redirect('/admin/categories');
    }

}
