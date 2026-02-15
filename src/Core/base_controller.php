<?php

declare(strict_types=1);

namespace App\Core;

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
                'id'         => (int) $_SESSION['user_id'],
                'name'       => $_SESSION['user_name'] ?? '',
                'first_name' => $_SESSION['user_first_name'] ?? '',
                'role'       => $_SESSION['user_role'] ?? 'member',
                'avatar'     => $_SESSION['user_avatar'] ?? null,
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

        $backUrl = '/';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referer !== '') {
            $parsed  = parse_url($referer);
            $refHost = ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
            $curHost = $_SERVER['HTTP_HOST'] ?? '';

            if ($refHost === $curHost && isset($parsed['path'])) {
                $backUrl = $parsed['path'];

                if (isset($parsed['query'])) {
                    $backUrl .= '?' . $parsed['query'];
                }
            }
        }

        return [
            'isLoggedIn'  => $isLoggedIn,
            'authUser'    => $authUser,
            'csrfToken'   => $_SESSION['csrf_token'] ?? '',
            'currentPage' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            'currentTos'  => $currentTos,
            'tosAccepted' => $tosAccepted,
            'unreadCount' => self::$cachedUnreadCount,
            'backUrl'     => $backUrl,
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
     * Redirect to a URL and halt execution.
     */
    protected function redirect(string $url): never
    {
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
        $posted  = $_POST['csrf_token'] ?? '';
        $session = $_SESSION['csrf_token'] ?? '';

        if ($session === '' || !hash_equals($session, $posted)) {
            $this->abort(403);
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
     * Halt execution and display an error page.
     */
    protected function abort(int $code): never
    {
        http_response_code($code);

        $errorPage = match ($code) {
            403 => BASE_PATH . '/src/Views/errors/403.php',
            404 => BASE_PATH . '/src/Views/errors/404.php',
            default => BASE_PATH . '/src/Views/errors/500.php',
        };

        require $errorPage;
        exit;
    }
}
