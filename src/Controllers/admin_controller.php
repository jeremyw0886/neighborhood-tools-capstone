<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Role;
use App\Models\Account;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Event;
use App\Models\Incident;
use App\Models\Neighborhood;
use App\Models\PlatformStats;
use App\Models\Tool;
use App\Models\Tos;
use App\Models\VectorImage;
use App\Models\AvatarVector;

class AdminController extends BaseController
{
    private const int PER_PAGE             = 12;
    private const int IMAGES_PER_PAGE      = 4;
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

    private const array USERS_SORT_FIELDS     = ['full_name', 'role_name_rol', 'account_status', 'overall_avg_rating', 'tools_owned', 'member_since'];
    private const array USERS_ALLOWED_ROLES    = ['member', 'admin', 'super_admin'];
    private const array USERS_ALLOWED_STATUSES = ['active', 'suspended', 'pending'];

    /**
     * User management — paginated, sortable, filterable list of platform members.
     *
     * Accepts `?q`, `?role`, `?status`, `?sort`, `?dir` query params.
     */
    public function users(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $flash = $_SESSION['admin_users_flash'] ?? null;
        unset($_SESSION['admin_users_flash']);

        $search     = $this->parseSearchQuery();
        $sortParams = $this->parseSortParams('', self::USERS_SORT_FIELDS, 'full_name', 'ASC');

        $rawRole = $_GET['role'] ?? '';
        $role    = in_array($rawRole, self::USERS_ALLOWED_ROLES, true) ? $rawRole : null;

        $rawStatus = $_GET['status'] ?? '';
        $status    = in_array($rawStatus, self::USERS_ALLOWED_STATUSES, true) ? $rawStatus : null;

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Account::getFilteredCount($role, $status, $search);
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $users      = Account::getAllForAdmin(
                limit:  self::PER_PAGE,
                offset: $offset,
                sort:   $sortParams['sort'],
                dir:    $sortParams['dir'],
                role:   $role,
                status: $status,
                search: $search,
            );
        } catch (\Throwable $e) {
            error_log('AdminController::users — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $users      = [];
        }

        $filterParams = array_filter([
            'q'      => $search,
            'role'   => $role,
            'status' => $status,
            'sort'   => $sortParams['sort'],
            'dir'    => $sortParams['dir'],
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/users', [
            'title'        => 'Manage Users — NeighborhoodTools',
            'description'  => 'View and manage platform members.',
            'pageCss'      => ['admin.css'],
            'users'        => $users,
            'totalCount'   => $totalCount,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => self::PER_PAGE,
            'flash'        => $flash,
            'search'       => $search,
            'role'         => $role,
            'status'       => $status,
            'sort'         => $sortParams['sort'],
            'dir'          => $sortParams['dir'],
            'filterParams' => $filterParams,
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

    private const array TOOLS_SORT_FIELDS        = ['tool_name_tol', 'owner_name', 'tool_condition', 'rental_fee_tol', 'avg_rating', 'total_borrows', 'incident_count', 'created_at_tol'];
    private const array TOOLS_ALLOWED_CONDITIONS = ['new', 'good', 'fair', 'poor'];

    /**
     * Tool management — paginated, sortable, filterable list of all tools.
     *
     * Accepts `?q`, `?condition`, `?incidents`, `?sort`, `?dir` query params.
     */
    public function tools(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $search     = $this->parseSearchQuery();
        $sortParams = $this->parseSortParams('', self::TOOLS_SORT_FIELDS, 'created_at_tol', 'DESC');

        $rawCondition = $_GET['condition'] ?? '';
        $condition    = in_array($rawCondition, self::TOOLS_ALLOWED_CONDITIONS, true) ? $rawCondition : null;

        $incidentsOnly = ($_GET['incidents'] ?? '') === '1';

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Tool::getAdminFilteredCount($condition, $incidentsOnly, $search);
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $tools      = Tool::getAdminList(
                limit:         self::PER_PAGE,
                offset:        $offset,
                sort:          $sortParams['sort'],
                dir:           $sortParams['dir'],
                condition:     $condition,
                incidentsOnly: $incidentsOnly,
                search:        $search,
            );
        } catch (\Throwable $e) {
            error_log('AdminController::tools — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $tools      = [];
        }

        $filterParams = array_filter([
            'q'         => $search,
            'condition' => $condition,
            'incidents' => $incidentsOnly ? '1' : null,
            'sort'      => $sortParams['sort'],
            'dir'       => $sortParams['dir'],
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/tools', [
            'title'         => 'Manage Tools — NeighborhoodTools',
            'description'   => 'View and manage listed tools.',
            'pageCss'       => ['admin.css'],
            'tools'         => $tools,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
            'search'        => $search,
            'condition'     => $condition,
            'incidentsOnly' => $incidentsOnly,
            'sort'          => $sortParams['sort'],
            'dir'           => $sortParams['dir'],
            'filterParams'  => $filterParams,
        ]);
    }

    private const array EVENTS_SORT_FIELDS    = ['start_at_evt', 'attendee_count', 'created_at_evt', 'event_name_evt'];
    private const array EVENTS_ALLOWED_TIMINGS = ['HAPPENING NOW', 'THIS WEEK', 'THIS MONTH', 'UPCOMING'];

    /**
     * Event management — sortable, filterable list of upcoming community events.
     *
     * Accepts `?timing`, `?sort`, `?dir` query params.
     */
    public function events(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $sortParams = $this->parseSortParams('', self::EVENTS_SORT_FIELDS, 'start_at_evt', 'ASC');

        $rawTiming = strtoupper(trim($_GET['timing'] ?? ''));
        $timing    = in_array($rawTiming, self::EVENTS_ALLOWED_TIMINGS, true) ? $rawTiming : null;

        try {
            $page         = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount   = Event::getUpcomingCount($timing);
            $timingCounts = Event::getTimingCounts();
            $totalPages   = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page         = min($page, $totalPages);
            $offset       = ($page - 1) * self::PER_PAGE;
            $events       = Event::getUpcoming(
                sort:   $sortParams['sort'],
                dir:    $sortParams['dir'],
                timing: $timing,
                limit:  self::PER_PAGE,
                offset: $offset,
            );
        } catch (\Throwable $e) {
            error_log('AdminController::events — ' . $e->getMessage());
            $page         = 1;
            $totalCount   = 0;
            $totalPages   = 1;
            $timingCounts = [];
            $events       = [];
        }

        $filterParams = array_filter([
            'timing' => $timing,
            'sort'   => $sortParams['sort'],
            'dir'    => $sortParams['dir'],
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/events', [
            'title'         => 'Manage Events — NeighborhoodTools',
            'description'   => 'View and manage community events.',
            'pageCss'       => ['admin.css'],
            'events'        => $events,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
            'timing'        => $timing,
            'timingCounts'  => $timingCounts,
            'sort'          => $sortParams['sort'],
            'dir'           => $sortParams['dir'],
            'filterParams'  => $filterParams,
        ]);
    }

    private const array INCIDENTS_SORT_FIELDS   = ['created_at_irt', 'days_open', 'incident_type', 'estimated_damage_amount_irt'];
    private const array INCIDENTS_ALLOWED_TYPES  = ['damage', 'theft', 'loss', 'injury', 'late_return', 'condition_dispute', 'other'];

    /**
     * Incident management — sortable, filterable list of open incident reports.
     *
     * Accepts `?type`, `?deadline`, `?sort`, `?dir` query params.
     */
    public function incidents(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $sortParams = $this->parseSortParams('', self::INCIDENTS_SORT_FIELDS, 'created_at_irt', 'DESC');

        $rawType = $_GET['type'] ?? '';
        $type    = in_array($rawType, self::INCIDENTS_ALLOWED_TYPES, true) ? $rawType : null;

        $rawDeadline = $_GET['deadline'] ?? '';
        $deadlineMet = match ($rawDeadline) {
            'met'    => true,
            'missed' => false,
            default  => null,
        };

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Incident::getFilteredOpenCount($type, $deadlineMet);
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $incidents  = Incident::getOpen(
                limit:       self::PER_PAGE,
                offset:      $offset,
                sort:        $sortParams['sort'],
                dir:         $sortParams['dir'],
                type:        $type,
                deadlineMet: $deadlineMet,
            );
        } catch (\Throwable $e) {
            error_log('AdminController::incidents — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $incidents  = [];
        }

        $filterParams = array_filter([
            'type'     => $type,
            'deadline' => $rawDeadline !== '' && $deadlineMet !== null ? $rawDeadline : null,
            'sort'     => $sortParams['sort'],
            'dir'      => $sortParams['dir'],
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/incidents', [
            'title'        => 'Manage Incidents — NeighborhoodTools',
            'description'  => 'Review open incident reports.',
            'pageCss'      => ['admin.css'],
            'incidents'    => $incidents,
            'totalCount'   => $totalCount,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => self::PER_PAGE,
            'type'         => $type,
            'deadlineMet'  => $deadlineMet,
            'sort'         => $sortParams['sort'],
            'dir'          => $sortParams['dir'],
            'filterParams' => $filterParams,
        ]);
    }

    private const array REPORTS_SORT_FIELDS = ['neighborhood_name_nbh', 'active_members', 'available_tools', 'active_borrows', 'upcoming_events'];

    /**
     * Reports — sortable neighborhood statistics from materialized summaries.
     *
     * Accepts `?sort`, `?dir` query params.
     */
    public function reports(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $sortParams = $this->parseSortParams('', self::REPORTS_SORT_FIELDS, 'neighborhood_name_nbh', 'ASC');

        try {
            $page          = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount    = Neighborhood::getSummaryCount();
            $totalPages    = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page          = min($page, $totalPages);
            $offset        = ($page - 1) * self::PER_PAGE;
            $neighborhoods = Neighborhood::getSummaryList(
                limit:  self::PER_PAGE,
                offset: $offset,
                sort:   $sortParams['sort'],
                dir:    $sortParams['dir'],
            );
        } catch (\Throwable $e) {
            error_log('AdminController::reports — ' . $e->getMessage());
            $page          = 1;
            $totalCount    = 0;
            $totalPages    = 1;
            $neighborhoods = [];
        }

        $filterParams = array_filter([
            'sort' => $sortParams['sort'],
            'dir'  => $sortParams['dir'],
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/reports', [
            'title'          => 'Reports — NeighborhoodTools',
            'description'    => 'Platform reports and statistics.',
            'pageCss'        => ['admin.css'],
            'neighborhoods'  => $neighborhoods,
            'totalCount'     => $totalCount,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'perPage'        => self::PER_PAGE,
            'sort'           => $sortParams['sort'],
            'dir'            => $sortParams['dir'],
            'filterParams'   => $filterParams,
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

    private const array TOS_SORT_FIELDS = ['full_name', 'last_login_at_acc', 'last_accepted_version'];

    /**
     * TOS management — current version and sortable non-compliant user listing.
     *
     * Accepts `?sort`, `?dir` query params.
     */
    public function tos(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $sortParams = $this->parseSortParams('', self::TOS_SORT_FIELDS, 'last_login_at_acc', 'DESC');

        try {
            $page       = max(1, (int) ($_GET['page'] ?? 1));
            $totalCount = Tos::getNonCompliantCount();
            $totalPages = max(1, (int) ceil($totalCount / self::PER_PAGE));
            $page       = min($page, $totalPages);
            $offset     = ($page - 1) * self::PER_PAGE;
            $users      = Tos::getNonCompliantUsers(
                limit:  self::PER_PAGE,
                offset: $offset,
                sort:   $sortParams['sort'],
                dir:    $sortParams['dir'],
            );
        } catch (\Throwable $e) {
            error_log('AdminController::tos — ' . $e->getMessage());
            $page       = 1;
            $totalCount = 0;
            $totalPages = 1;
            $users      = [];
        }

        $filterParams = array_filter([
            'sort' => $sortParams['sort'],
            'dir'  => $sortParams['dir'],
        ], static fn(mixed $v): bool => $v !== null);

        $this->render('admin/tos', [
            'title'        => 'Manage Terms of Service — NeighborhoodTools',
            'description'  => 'View and manage Terms of Service versions.',
            'pageCss'      => ['admin.css'],
            'users'        => $users,
            'totalCount'   => $totalCount,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => self::PER_PAGE,
            'sort'         => $sortParams['sort'],
            'dir'          => $sortParams['dir'],
            'filterParams' => $filterParams,
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
            $_SESSION['admin_images_flash'] = 'No file uploaded or upload error occurred.';
            $this->redirect('/admin/images');
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if ($mimeType !== 'image/svg+xml') {
            $_SESSION['admin_images_flash'] = 'Only SVG files are allowed.';
            $this->redirect('/admin/images');
        }

        if ($file['size'] > 1_048_576) {
            $_SESSION['admin_images_flash'] = 'File must be under 1 MB.';
            $this->redirect('/admin/images');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'svg') {
            $_SESSION['admin_images_flash'] = 'File must have an .svg extension.';
            $this->redirect('/admin/images');
        }

        $fileName = uniqid('vec_', true) . '.svg';
        $destPath = BASE_PATH . '/public/uploads/vectors/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $_SESSION['admin_images_flash'] = 'Failed to save uploaded file.';
            $this->redirect('/admin/images');
        }

        try {
            VectorImage::create(
                $fileName,
                $description !== '' ? $description : null,
                (int) $_SESSION['user_id']
            );
            $_SESSION['admin_images_flash'] = 'Category icon uploaded successfully.';
        } catch (\Throwable $e) {
            error_log('AdminController::uploadVector — ' . $e->getMessage());
            @unlink($destPath);
            $_SESSION['admin_images_flash'] = 'Failed to save vector image record.';
        }

        $this->redirect('/admin/images');
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

    /** Images tab — category icons and avatar vectors (paginated). */
    public function images(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);

        $flash = $_SESSION['admin_images_flash'] ?? null;
        unset($_SESSION['admin_images_flash']);

        $perPage = self::IMAGES_PER_PAGE;

        try {
            $iconsPage       = max(1, (int) ($_GET['icons_page'] ?? 1));
            $iconsTotalCount = VectorImage::getCount();
            $iconsTotalPages = max(1, (int) ceil($iconsTotalCount / $perPage));
            $iconsPage       = min($iconsPage, $iconsTotalPages);
            $iconsOffset     = ($iconsPage - 1) * $perPage;
            $categoryVectors = VectorImage::getPaged($perPage, $iconsOffset);

            $avatarsPage       = max(1, (int) ($_GET['avatars_page'] ?? 1));
            $avatarsTotalCount = AvatarVector::getCount();
            $avatarsTotalPages = max(1, (int) ceil($avatarsTotalCount / $perPage));
            $avatarsPage       = min($avatarsPage, $avatarsTotalPages);
            $avatarsOffset     = ($avatarsPage - 1) * $perPage;
            $avatarVectors     = AvatarVector::getPaged($perPage, $avatarsOffset);
        } catch (\Throwable $e) {
            error_log('AdminController::images — ' . $e->getMessage());
            $categoryVectors   = [];
            $avatarVectors     = [];
            $iconsPage         = 1;
            $iconsTotalPages   = 1;
            $iconsTotalCount   = 0;
            $avatarsPage       = 1;
            $avatarsTotalPages = 1;
            $avatarsTotalCount = 0;
        }

        $iconsFilterParams   = array_filter(['avatars_page' => $avatarsPage > 1 ? $avatarsPage : null]);
        $avatarsFilterParams = array_filter(['icons_page' => $iconsPage > 1 ? $iconsPage : null]);

        $this->render('admin/images', [
            'title'              => 'Manage Images — NeighborhoodTools',
            'description'        => 'Manage category icons and profile avatar vectors.',
            'pageCss'            => ['admin.css'],
            'categoryVectors'    => $categoryVectors,
            'avatarVectors'      => $avatarVectors,
            'flash'              => $flash,
            'iconsPage'          => $iconsPage,
            'iconsTotalPages'    => $iconsTotalPages,
            'iconsTotalCount'    => $iconsTotalCount,
            'avatarsPage'        => $avatarsPage,
            'avatarsTotalPages'  => $avatarsTotalPages,
            'avatarsTotalCount'  => $avatarsTotalCount,
            'iconsFilterParams'  => $iconsFilterParams,
            'avatarsFilterParams' => $avatarsFilterParams,
        ]);
    }

    /** Update a category vector's description. */
    public function updateVectorDescription(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $vectorId = (int) $id;

        if ($vectorId < 1) {
            $this->abort(404);
        }

        $description = trim($_POST['description'] ?? '');

        try {
            VectorImage::updateDescription($vectorId, $description !== '' ? $description : null);
            $_SESSION['admin_images_flash'] = 'Description updated.';
        } catch (\Throwable $e) {
            error_log('AdminController::updateVectorDescription — ' . $e->getMessage());
            $_SESSION['admin_images_flash'] = 'Failed to update description.';
        }

        $this->redirect('/admin/images');
    }

    /** Delete a category vector (blocked if assigned to a category). */
    public function deleteVector(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $vectorId = (int) $id;

        if ($vectorId < 1) {
            $this->abort(404);
        }

        try {
            $vector = VectorImage::findById($vectorId);

            if ($vector === null) {
                $this->abort(404);
            }

            if (!VectorImage::delete($vectorId)) {
                $_SESSION['admin_images_flash'] = 'Cannot delete — icon is assigned to a category.';
                $this->redirect('/admin/images');
            }

            $filePath = BASE_PATH . '/public/uploads/vectors/' . $vector['file_name_vec'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            $_SESSION['admin_images_flash'] = 'Category icon deleted.';
        } catch (\Throwable $e) {
            error_log('AdminController::deleteVector — ' . $e->getMessage());
            $_SESSION['admin_images_flash'] = 'Failed to delete category icon.';
        }

        $this->redirect('/admin/images');
    }

    /** Upload a new avatar vector SVG. */
    public function uploadAvatarVector(): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $file        = $_FILES['vector_file'] ?? null;
        $description = trim($_POST['description'] ?? '');

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['admin_images_flash'] = 'No file uploaded or upload error occurred.';
            $this->redirect('/admin/images');
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if ($mimeType !== 'image/svg+xml') {
            $_SESSION['admin_images_flash'] = 'Only SVG files are allowed.';
            $this->redirect('/admin/images');
        }

        if ($file['size'] > 1_048_576) {
            $_SESSION['admin_images_flash'] = 'File must be under 1 MB.';
            $this->redirect('/admin/images');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'svg') {
            $_SESSION['admin_images_flash'] = 'File must have an .svg extension.';
            $this->redirect('/admin/images');
        }

        $fileName = uniqid('avt_', true) . '.svg';
        $destPath = BASE_PATH . '/public/uploads/vectors/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $_SESSION['admin_images_flash'] = 'Failed to save uploaded file.';
            $this->redirect('/admin/images');
        }

        try {
            AvatarVector::create(
                $fileName,
                $description !== '' ? $description : null,
                (int) $_SESSION['user_id']
            );
            $_SESSION['admin_images_flash'] = 'Avatar vector uploaded successfully.';
        } catch (\Throwable $e) {
            error_log('AdminController::uploadAvatarVector — ' . $e->getMessage());
            @unlink($destPath);
            $_SESSION['admin_images_flash'] = 'Failed to save avatar vector record.';
        }

        $this->redirect('/admin/images');
    }

    /** Update an avatar vector's description. */
    public function updateAvatarVectorDescription(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $vectorId = (int) $id;

        if ($vectorId < 1) {
            $this->abort(404);
        }

        $description = trim($_POST['description'] ?? '');

        try {
            AvatarVector::updateDescription($vectorId, $description !== '' ? $description : null);
            $_SESSION['admin_images_flash'] = 'Description updated.';
        } catch (\Throwable $e) {
            error_log('AdminController::updateAvatarVectorDescription — ' . $e->getMessage());
            $_SESSION['admin_images_flash'] = 'Failed to update description.';
        }

        $this->redirect('/admin/images');
    }

    /** Toggle an avatar vector's active status. */
    public function toggleAvatarVector(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $vectorId = (int) $id;

        if ($vectorId < 1) {
            $this->abort(404);
        }

        try {
            AvatarVector::toggleActive($vectorId);
            $_SESSION['admin_images_flash'] = 'Avatar vector status toggled.';
        } catch (\Throwable $e) {
            error_log('AdminController::toggleAvatarVector — ' . $e->getMessage());
            $_SESSION['admin_images_flash'] = 'Failed to toggle avatar vector status.';
        }

        $this->redirect('/admin/images');
    }

    /** Delete an avatar vector (blocked if any user has it selected). */
    public function deleteAvatarVector(string $id): void
    {
        $this->requireRole(Role::Admin, Role::SuperAdmin);
        $this->validateCsrf();

        $vectorId = (int) $id;

        if ($vectorId < 1) {
            $this->abort(404);
        }

        try {
            $vector = AvatarVector::findById($vectorId);

            if ($vector === null) {
                $this->abort(404);
            }

            if (!AvatarVector::delete($vectorId)) {
                $_SESSION['admin_images_flash'] = 'Cannot delete — avatar is selected by one or more users.';
                $this->redirect('/admin/images');
            }

            $filePath = BASE_PATH . '/public/uploads/vectors/' . $vector['file_name_avv'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            $_SESSION['admin_images_flash'] = 'Avatar vector deleted.';
        } catch (\Throwable $e) {
            error_log('AdminController::deleteAvatarVector — ' . $e->getMessage());
            $_SESSION['admin_images_flash'] = 'Failed to delete avatar vector.';
        }

        $this->redirect('/admin/images');
    }

}
