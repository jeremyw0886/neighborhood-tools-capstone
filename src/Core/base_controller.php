<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\RateLimiter;
use App\Models\Notification;
use App\Models\Tos;

class BaseController
{
    /** Cache for current TOS to avoid repeated queries within a request. */
    private static ?array $cachedTos = null;
    private static bool $tosCacheLoaded = false;

    /** Cache for unread notification count — same pattern as TOS cache. */
    private static ?int $cachedUnreadCount = null;
    private static bool $unreadCacheLoaded = false;

    /**
     * Build the shared data array available to every view.
     *
     * @return array{isLoggedIn: bool, authUser: ?array, csrfToken: string,
     *               currentPage: string, currentTos: ?array, tosAccepted: bool,
     *               unreadCount: int}
     */
    protected function getSharedData(): array
    {
        $isLoggedIn = !empty($_SESSION['logged_in']);

        $authUser = $isLoggedIn
            ? [
                'id'            => (int) $_SESSION['user_id'],
                'name'          => $_SESSION['user_name'] ?? '',
                'first_name'    => $_SESSION['user_first_name'] ?? '',
                'role'          => $_SESSION['user_role'] ?? 'member',
                'avatar'        => $_SESSION['user_avatar'] ?? null,
                'vector_avatar' => $_SESSION['user_vector_avatar'] ?? null,
            ]
            : null;

        // Cache TOS query — lightweight
        if (!self::$tosCacheLoaded) {
            self::$cachedTos = Tos::getCurrent();
            self::$tosCacheLoaded = true;
        }

        $currentTos = self::$cachedTos;

        // Safe default: if no active TOS exists, treat as accepted so users aren't blocked
        $tosAccepted = $currentTos === null
            || !$isLoggedIn
            || Tos::hasUserAccepted(accountId: $authUser['id'], tosId: (int) $currentTos['id_tos']);

        // Cache unread notification count — cheap indexed query
        if (!self::$unreadCacheLoaded) {
            self::$cachedUnreadCount = $isLoggedIn
                ? Notification::getUnreadCount($authUser['id'])
                : 0;
            self::$unreadCacheLoaded = true;
        }

        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $backUrls    = $_SESSION['_back_urls'] ?? [];
        $backUrl     = $backUrls[$currentPath] ?? '/';
        $referer     = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referer !== '') {
            $parsed  = parse_url($referer);
            $refHost = ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
            $curHost = $_SERVER['HTTP_HOST'] ?? '';

            if ($refHost === $curHost && isset($parsed['path'])) {
                $refPath = $parsed['path'];
                $refUrl  = $refPath . (isset($parsed['query']) ? '?' . $parsed['query'] : '');

                if ($refPath === $currentPath) {
                    // Same page (POST-redirect-GET) — keep stored value
                } else {
                    $refBackPath = isset($backUrls[$refPath])
                        ? (parse_url($backUrls[$refPath], PHP_URL_PATH) ?: '/')
                        : null;

                    if ($refBackPath !== $currentPath) {
                        $backUrls[$currentPath] = $refUrl;
                        $backUrl = $refUrl;
                    }
                }
            }
        }

        if (count($backUrls) > 20) {
            $backUrls = array_slice($backUrls, -20, preserve_keys: true);
        }
        $_SESSION['_back_urls'] = $backUrls;

        $flashError = $_SESSION['_flash_error'] ?? null;
        unset($_SESSION['_flash_error']);

        return [
            'isLoggedIn'  => $isLoggedIn,
            'authUser'    => $authUser,
            'csrfToken'   => $_SESSION['csrf_token'] ?? '',
            'currentPage' => $currentPath,
            'currentTos'  => $currentTos,
            'tosAccepted' => $tosAccepted,
            'unreadCount' => self::$cachedUnreadCount,
            'backUrl'     => $backUrl,
            'flashError'  => $flashError,
        ];
    }

    protected const array ADMIN_SECTIONS = [
        'dashboard'      => ['heading' => 'Admin Dashboard',           'description' => 'Platform overview and management tools.',                                                      'icon' => 'fa-solid fa-shield-halved',      'id' => 'admin-heading'],
        'users'          => ['heading' => 'Manage Users',              'description' => 'Platform members with rating summaries and account status management.',                         'icon' => 'fa-solid fa-users',              'id' => 'admin-users-heading'],
        'tools'          => ['heading' => 'Manage Tools',              'description' => 'Platform-wide tool listings with borrow statistics, ratings, and incident counts.',              'icon' => 'fa-solid fa-screwdriver-wrench', 'id' => 'admin-tools-heading'],
        'disputes'       => ['heading' => 'Manage Disputes',           'description' => 'Review and resolve open disputes between members.',                                             'icon' => 'fa-solid fa-gavel',              'id' => 'admin-disputes-heading'],
        'events'         => ['heading' => 'Manage Events',             'description' => 'View and manage upcoming community events.',                                                    'icon' => 'fa-solid fa-calendar',           'id' => 'admin-events-heading'],
        'incidents'      => ['heading' => 'Manage Incidents',          'description' => 'Review and resolve open incident reports.',                                                     'icon' => 'fa-solid fa-flag',               'id' => 'admin-incidents-heading'],
        'deposits'       => ['heading' => 'Manage Deposits',           'description' => 'Security deposits across all borrows with status tracking, action flags, and incident history.', 'icon' => 'fa-solid fa-vault',              'id' => 'admin-deposits-heading'],
        'images'         => ['heading' => 'Manage Images',             'description' => 'Upload and manage category icons and profile avatar vectors.',                                  'icon' => 'fa-solid fa-images',             'id' => 'admin-images-heading'],
        'categories'     => ['heading' => 'Manage Categories',         'description' => 'Create, edit, and delete tool categories.',                                                    'icon' => 'fa-solid fa-tags',               'id' => 'admin-categories-heading'],
        'reports'        => ['heading' => 'Reports',                   'description' => 'Neighborhood statistics and community health metrics.',                                         'icon' => 'fa-solid fa-chart-bar',          'id' => 'admin-reports-heading'],
        'audit-log'      => ['heading' => 'Audit Log',                 'description' => 'Platform activity and audit trail.',                                                            'icon' => 'fa-solid fa-clipboard-list',     'id' => 'admin-audit-heading'],
        'tos'            => ['heading' => 'Manage Terms of Service',   'description' => 'Current version details and member acceptance status.',                                         'icon' => 'fa-solid fa-file-contract',      'id' => 'admin-tos-heading'],
        'tos-create'     => ['heading' => 'Create TOS Version',        'description' => 'Publish a new Terms of Service version. The current active version will be superseded.',        'icon' => 'fa-solid fa-file-circle-plus',   'id' => 'admin-tos-create-heading'],
        'search'         => ['heading' => 'Search Results',            'description' => '',                                                                                              'icon' => 'fa-solid fa-magnifying-glass',   'id' => 'admin-search-heading'],
        'purge-confirm'  => ['heading' => 'Permanently Purge Account', 'description' => '',                                                                                              'icon' => 'fa-solid fa-skull-crossbones',   'id' => 'purge-confirm-heading'],
    ];

    protected const array DASHBOARD_SECTIONS = [
        'overview', 'lender', 'borrower', 'history', 'loan-status',
        'list-tool', 'edit-tool', 'bookmarks', 'events', 'profile', 'profile-edit',
    ];

    /**
     * Render a dashboard view through the shell or as a partial for XHR.
     *
     * @param string $section One of DASHBOARD_SECTIONS
     * @param array  $data    Variables for the view
     */
    protected function renderDashboard(string $section, array $data = []): void
    {
        if (!in_array($section, self::DASHBOARD_SECTIONS, true)) {
            $this->abort(404);
        }

        $partial = BASE_PATH . '/src/Views/dashboard/' . $section . '.php';
        $data['dashboardSection'] = $section;
        $data['dashboardPartial'] = $partial;

        if ($this->isXhr()) {
            $this->renderPartial(
                BASE_PATH . '/src/Views/dashboard/index.php',
                $data,
            );
            return;
        }

        $this->render('dashboard/index', $data);
    }

    /**
     * Render an admin view through the shell or as a partial for XHR.
     *
     * @param string $section One of ADMIN_SECTIONS
     * @param array  $data    Variables for the view
     */
    protected function renderAdmin(string $section, array $data = []): void
    {
        if (!isset(self::ADMIN_SECTIONS[$section])) {
            $this->abort(404);
        }

        $meta    = self::ADMIN_SECTIONS[$section];
        $partial = BASE_PATH . '/src/Views/admin/' . $section . '.php';

        $data['adminSection']   = $section;
        $data['adminPartial']   = $partial;
        $data['adminSectionId'] = $meta['id'];
        $data['adminHeading']     ??= $meta['heading'];
        $data['adminDescription'] ??= $meta['description'];
        $data['adminIcon']        ??= $meta['icon'];

        if ($this->isXhr()) {
            header('X-Admin-Heading: ' . rawurlencode($data['adminHeading']));
            header('X-Admin-Icon: ' . $data['adminIcon']);
            header('X-Admin-Description: ' . rawurlencode($data['adminDescription']));
            header('X-Admin-Section-Id: ' . $data['adminSectionId']);
            $this->renderPartial($partial, $data);
            return;
        }

        $this->render('admin/index', $data);
    }

    /**
     * Render a partial view without the layout.
     *
     * @param string $partialPath Absolute path to the partial file
     * @param array  $data        Variables to extract into the partial
     */
    protected function renderPartial(string $partialPath, array $data = []): void
    {
        header('X-Partial: 1');

        if (isset($data['title'])) {
            header('X-Page-Title: ' . rawurlencode($data['title']));
        }

        if (!empty($data['pageCss'])) {
            $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
            $hrefs = array_map(
                static fn(string $file): string =>
                    '/assets/css/' . ($isDev ? $file : str_replace('.css', '.min.css', $file))
                    . '?v=' . ASSET_VERSION,
                $data['pageCss'],
            );
            header('X-Page-Css: ' . implode(', ', $hrefs));
        }

        $data = array_merge($this->getSharedData(), $data);
        extract($data);

        require $partialPath;
    }

    /**
     * Render a view inside the main layout.
     *
     * Shared data (auth state, CSRF, current page) is merged first,
     * then controller-provided $data is merged on top — so controllers
     * can override any shared value if needed.
     *
     * @param string $view  Path relative to Views/ (e.g. 'home/index')
     * @param array  $data  Variables to extract into the view
     */
    protected function render(string $view, array $data = []): void
    {
        extract(array_merge($this->getSharedData(), $data));

        ob_start();
        require BASE_PATH . '/src/Views/' . $view . '.php';
        $content = ob_get_clean();

        require BASE_PATH . '/src/Views/layouts/main.php';
    }

    /**
     * Read and clear a flash value from the session in one step.
     */
    protected function flash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    /**
     * Redirect to a URL and halt execution.
     */
    protected function redirect(string $url): never
    {
        $url = str_replace(["\r", "\n"], '', $url);

        if ($url === '' || $url[0] !== '/') {
            $url = '/';
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Require an authenticated session; redirect to login if not.
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['logged_in'])) {
            if ($this->isXhr() || $this->wantsJson()) {
                $this->jsonResponse(401, ['success' => false, 'message' => 'Session expired.']);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            }
            $this->redirect('/login');
        }
    }

    /**
     * Require one of the given roles; abort 403 if the user's role doesn't match.
     */
    protected function requireRole(Role ...$roles): void
    {
        $this->requireAuth();

        $userRole = Role::tryFrom($_SESSION['user_role'] ?? '');

        if ($userRole === null || !in_array($userRole, $roles, true)) {
            $this->abort(403);
        }
    }

    /**
     * Validate the CSRF token from a POST request against the session token.
     */
    protected function validateCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($_POST)
            && empty($_FILES)
            && isset($_SERVER['CONTENT_LENGTH'])
            && (int) $_SERVER['CONTENT_LENGTH'] > 0
        ) {
            $maxSize = ini_get('post_max_size') ?: '8M';
            $_SESSION['_flash_error'] = "Upload too large (server limit: {$maxSize}). Please choose a smaller file.";
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $path = parse_url($referer, PHP_URL_PATH);
            $this->redirect(is_string($path) && $path !== '' ? $path : '/');
        }

        $fromPost   = $_POST['csrf_token'] ?? '';
        $fromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if ($fromPost !== '' && $fromHeader !== '' && !hash_equals($fromPost, $fromHeader)) {
            $this->abort(403);
        }

        $posted  = $fromPost !== '' ? $fromPost : $fromHeader;
        $session = $_SESSION['csrf_token'] ?? '';

        if ($session === '' || !hash_equals($session, $posted)) {
            if (empty($_SESSION['logged_in'])) {
                $_SESSION['auth_error'] = 'Your session has expired. Please log in again.';
                $this->redirect('/login');
            }

            $this->abort(403);
        }
    }

    /**
     * Verify a Cloudflare Turnstile token via the siteverify API.
     *
     * @param string $token  The cf-turnstile-response value from the form
     * @param string $action Expected action name (must match the widget's data-action)
     * @return bool
     */
    protected function verifyTurnstile(string $token, string $action): bool
    {
        $secret = trim($_ENV['TURNSTILE_SECRET_KEY'] ?? '');

        if ($secret === '') {
            return true;
        }

        $jsEnabled = ($_POST['js_enabled'] ?? '') === '1';

        if ($jsEnabled) {
            $_SESSION['js_capable'] = true;
        }

        $requireToken = $jsEnabled || !empty($_SESSION['js_capable']);

        if ($token === '') {
            return !$requireToken;
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'secret'   => $secret,
                    'response' => $token,
                ]),
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            false,
            $context,
        );

        if ($response === false) {
            error_log('Turnstile verification request failed');
            return false;
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            error_log('Turnstile returned invalid JSON');
            return false;
        }

        if (empty($result['success'])) {
            error_log('Turnstile verification failed: ' . json_encode($result['error-codes'] ?? []));
            return false;
        }

        $hostname = $this->normalizeHostname($result['hostname'] ?? '');
        $allowedHostnames = $this->getAllowedTurnstileHostnames();

        if ($hostname === '') {
            error_log('Turnstile hostname missing from verification response');
            return false;
        }

        if ($allowedHostnames !== [] && !in_array($hostname, $allowedHostnames, true)) {
            error_log("Turnstile hostname mismatch: '{$hostname}'");
            return false;
        }

        if (($result['action'] ?? '') !== $action) {
            error_log("Turnstile action mismatch: expected '{$action}', got '{$result['action']}'");
            return false;
        }

        return true;
    }

    /**
     * Build the allowlist of expected Turnstile hostnames for this environment.
     *
     * Sources:
     * - TURNSTILE_ALLOWED_HOSTNAMES (comma-separated)
     * - APP_URL host
     * - current request host
     *
     * @return string[]
     */
    protected function getAllowedTurnstileHostnames(): array
    {
        $hostnames = [];

        $configured = trim($_ENV['TURNSTILE_ALLOWED_HOSTNAMES'] ?? '');
        if ($configured !== '') {
            foreach (explode(',', $configured) as $entry) {
                $host = $this->normalizeHostname($entry);
                if ($host !== '') {
                    $hostnames[] = $host;
                }
            }
        }

        $appUrlHost = $this->normalizeHostname($_ENV['APP_URL'] ?? '');
        if ($appUrlHost !== '') {
            $hostnames[] = $appUrlHost;
        }

        $requestHost = $this->normalizeHostname($_SERVER['HTTP_HOST'] ?? '');
        if ($requestHost !== '') {
            $hostnames[] = $requestHost;
        }

        return array_values(array_unique($hostnames));
    }

    /**
     * Normalize a hostname or URL into a lowercase host without port brackets.
     */
    protected function normalizeHostname(string $value): string
    {
        $value = trim(strtolower($value));
        if ($value === '') {
            return '';
        }

        $candidate = str_contains($value, '://') ? $value : 'http://' . $value;
        $host = parse_url($candidate, PHP_URL_HOST);

        return is_string($host) ? trim($host, '[]') : '';
    }

    /**
     * Enforce rate limiting on the current request.
     *
     * Call at the top of state-changing actions, after validateCsrf().
     * If the limit is exceeded, flashes a message and redirects (never returns).
     *
     * Flash keys support dot notation for nested arrays:
     *   'auth_error'            → $_SESSION['auth_error'] = message
     *   'register_errors.general' → $_SESSION['register_errors'] = ['general' => message]
     */
    protected function checkRateLimit(
        string $action,
        string $redirectTo,
        string $flashKey,
        string $flashValue = 'Too many attempts. Please try again in {minutes}.',
    ): void {
        static $config = null;
        $config ??= require BASE_PATH . '/config/rate-limit.php';

        $limits = $config[$action] ?? null;

        if ($limits === null) {
            return;
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = $ip . '|' . $action;

        if (!RateLimiter::tooManyAttempts($key, $limits['max_attempts'], $limits['window_seconds'])) {
            return;
        }

        $remaining = RateLimiter::remainingSeconds($key, $limits['window_seconds']);
        $minutes   = (int) ceil($remaining / 60);
        $label     = $minutes === 1 ? '1 minute' : $minutes . ' minutes';
        $message   = str_replace('{minutes}', $label, $flashValue);

        if (str_contains($flashKey, '.')) {
            [$sessionKey, $subKey] = explode('.', $flashKey, 2);
            $_SESSION[$sessionKey] = [$subKey => $message];
        } else {
            $_SESSION[$flashKey] = $message;
        }

        $this->redirect($redirectTo);
    }

    /**
     * Read and decode the JSON request body.
     *
     * @return array Decoded JSON payload
     */
    protected function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === '' || $raw === false || !json_validate($raw)) {
            $this->jsonResponse(400, ['error' => 'Invalid JSON']);
        }

        return json_decode($raw, true);
    }

    /**
     * Check if the current request was made via XMLHttpRequest.
     */
    protected function isXhr(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Check if the client prefers a JSON response.
     */
    protected function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /**
     * Send a JSON response and halt execution.
     */
    protected function jsonResponse(int $statusCode, array $data): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo json_encode($data);
        exit;
    }

    /**
     * Parse and validate sort parameters from the query string.
     *
     * @param  string   $prefix        Param prefix (e.g. 'req_' reads 'req_sort', 'req_dir')
     * @param  string[] $allowedFields Permitted column names
     * @param  string   $defaultSort   Fallback sort field
     * @param  string   $defaultDir    Fallback direction (ASC or DESC)
     * @return array{sort: string, dir: string}
     */
    protected function parseSortParams(
        string $prefix,
        array $allowedFields,
        string $defaultSort,
        string $defaultDir,
    ): array {
        $sort = $_GET[$prefix . 'sort'] ?? '';
        $dir  = strtolower(trim($_GET[$prefix . 'dir'] ?? ''));

        return [
            'sort' => in_array($sort, $allowedFields, true) ? $sort : $defaultSort,
            'dir'  => in_array($dir, ['asc', 'desc'], true) ? strtoupper($dir) : $defaultDir,
        ];
    }

    /**
     * Parse and validate a status filter from the query string.
     *
     * @param  string   $prefix          Param prefix (e.g. 'borrow_' reads 'borrow_status')
     * @param  string[] $allowedStatuses Permitted status values
     * @return ?string  Validated status or null (show all)
     */
    protected function parseStatusFilter(string $prefix, array $allowedStatuses): ?string
    {
        $status = $_GET[$prefix . 'status'] ?? '';

        return in_array($status, $allowedStatuses, true) ? $status : null;
    }

    /**
     * Parse and sanitize a search query from the query string.
     *
     * @param  string $param     GET parameter name
     * @param  int    $maxLength Maximum allowed length
     * @return ?string Sanitized query or null if empty
     */
    protected function parseSearchQuery(string $param = 'q', int $maxLength = 100): ?string
    {
        $raw = trim($_GET[$param] ?? '');

        if ($raw === '') {
            return null;
        }

        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw);

        return mb_substr($clean, 0, $maxLength) ?: null;
    }

    /**
     * Resolve radius/zip defaults for tool browsing.
     *
     * @param array    $get          The $_GET superglobal
     * @param ?string  $userZip      Logged-in user's stored zip
     * @param int[]    $allowedRadii Valid radius values
     * @return array{radius: ?int, zip: ?string, radiusAutoApplied: bool}
     */
    protected static function resolveDefaultRadius(
        array $get,
        ?string $userZip,
        array $allowedRadii,
    ): array {
        $userExplicitlySetRadius = array_key_exists('radius', $get);
        $rawRadius  = (int) ($get['radius'] ?? 0);
        $radius     = in_array($rawRadius, $allowedRadii, true) ? $rawRadius : null;

        $zip = trim($get['zip'] ?? '') !== '' ? trim($get['zip']) : null;

        if ($zip !== null && !preg_match('/^\d{5}$/', $zip)) {
            $zip = null;
        }

        $radiusAutoApplied = false;

        if (!$userExplicitlySetRadius && $radius === null
            && !empty($userZip)) {
            $radius = 50;
            $zip    = $zip ?? $userZip;
            $radiusAutoApplied = true;
        }

        if ($zip === null && $radius !== null && !empty($userZip)) {
            $zip = $userZip;
        }

        if ($zip === null) {
            $radius = null;
            $radiusAutoApplied = false;
        }

        return [
            'radius'            => $radius,
            'zip'               => $zip,
            'radiusAutoApplied' => $radiusAutoApplied,
        ];
    }

    /**
     * Halt execution and display an error page.
     */
    protected function abort(int $code): never
    {
        http_response_code($code);

        extract($this->getSharedData());

        $errorPage = match ($code) {
            403 => BASE_PATH . '/src/Views/errors/403.php',
            404 => BASE_PATH . '/src/Views/errors/404.php',
            default => BASE_PATH . '/src/Views/errors/500.php',
        };

        require $errorPage;
        exit;
    }
}
