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
     * Views receive these variables automatically — no direct $_SESSION reads needed.
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

        // Cache TOS query — lightweight (single row) but no reason to repeat it
        if (!self::$tosCacheLoaded) {
            self::$cachedTos = Tos::getCurrent();
            self::$tosCacheLoaded = true;
        }

        $currentTos = self::$cachedTos;

        // Safe default: if no active TOS exists, treat as accepted so users aren't blocked
        $tosAccepted = $currentTos === null
            || !$isLoggedIn
            || Tos::hasUserAccepted(accountId: $authUser['id'], tosId: (int) $currentTos['id_tos']);

        // Cache unread notification count — cheap indexed query, but no reason to repeat
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
     *
     * Controllers call this before render() so views never touch $_SESSION.
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
            $this->redirect('/login');
        }
    }

    /**
     * Require one of the given roles; abort 403 if the user's role doesn't match.
     *
     * Uses the Role enum for type-safe comparison — no magic strings.
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
     *
     * Call this at the top of any state-changing action (login, register,
     * logout, form submissions, etc.).
     */
    protected function validateCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($_POST)
            && isset($_SERVER['CONTENT_LENGTH'])
            && (int) $_SERVER['CONTENT_LENGTH'] > 0
        ) {
            $maxSize = ini_get('post_max_size') ?: '8M';
            $_SESSION['_flash_error'] = "Upload too large (server limit: {$maxSize}). Please choose a smaller file.";
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $path = parse_url($referer, PHP_URL_PATH);
            $this->redirect(is_string($path) && $path !== '' ? $path : '/');
        }

        $posted  = $_POST['csrf_token'] ?? '';
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
        $secret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';

        if ($secret === '' || $token === '') {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
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
            return true;
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            error_log('Turnstile returned invalid JSON');
            return true;
        }

        if (empty($result['success'])) {
            error_log('Turnstile verification failed: ' . json_encode($result['error-codes'] ?? []));
            return false;
        }

        if (($result['action'] ?? '') !== $action) {
            error_log("Turnstile action mismatch: expected '{$action}', got '{$result['action']}'");
            return false;
        }

        return true;
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
     * Send a JSON response and halt execution.
     */
    protected function jsonResponse(int $statusCode, array $data): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
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
