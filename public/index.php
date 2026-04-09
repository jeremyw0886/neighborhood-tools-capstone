<?php

declare(strict_types=1);

/**
 * NeighborhoodTools Front Controller
 *
 * All web requests are routed through this file.
 */

define('BASE_PATH', dirname(__DIR__));

// Autoload dependencies
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Cache-busting version — must come after dotenv so APP_ENV is available
// Dev: timestamp on every request (never cached). Prod: content hash from build.
define('ASSET_VERSION', ($_ENV['APP_ENV'] ?? 'production') === 'development'
    ? (string) time()
    : require BASE_PATH . '/config/asset-version.php'
);

// Load configuration
$appConfig = require BASE_PATH . '/config/app.php';
$dbConfig  = require BASE_PATH . '/config/database.php';
$routes    = require BASE_PATH . '/config/routes.php';

$routesByMethod = [];
foreach ($routes as $route => $handler) {
    [$routeMethod, $routePath] = explode(' ', $route, 2);
    $routesByMethod[$routeMethod][] = ['path' => $routePath, 'handler' => $handler];
}

// Error display — show details in dev, suppress in production
if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Start session with secure cookie settings
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

$sessionLifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 1800);

$sessionOptions = [
    'cookie_httponly'  => true,
    'cookie_samesite'  => 'Lax',
    'cookie_secure'    => $isHttps,
    'gc_maxlifetime'   => $sessionLifetime,
    'cookie_lifetime'  => 0,
];

$sessionSavePath = BASE_PATH . '/storage/sessions';

if (is_dir($sessionSavePath) && is_writable($sessionSavePath)) {
    session_save_path($sessionSavePath);
}

session_cache_limiter('');
session_start($sessionOptions);

if (isset($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity']) > $sessionLifetime
) {
    session_unset();
    session_destroy();
    session_start($sessionOptions);
}
$_SESSION['last_activity'] = time();

// Re-verify role and account status from DB (TTL-cached for read requests)
if (!empty($_SESSION['logged_in'])) {
    $isWriteRequest = !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true);
    $roleTtl        = 10;
    $needsRefresh   = $isWriteRequest
        || !isset($_SESSION['_role_verified_at'])
        || (time() - $_SESSION['_role_verified_at']) >= $roleTtl;

    if ($needsRefresh) {
        $refreshStmt = \App\Core\Database::connection()->prepare(
            'SELECT r.role_name_rol, s.status_name_ast
             FROM account_acc a
             JOIN role_rol r ON a.id_rol_acc = r.id_rol
             JOIN account_status_ast s ON a.id_ast_acc = s.id_ast
             WHERE a.id_acc = :id
             LIMIT 1'
        );
        $refreshStmt->bindValue(':id', (int) $_SESSION['user_id'], \PDO::PARAM_INT);
        $refreshStmt->execute();
        $currentAccount = $refreshStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$currentAccount || $currentAccount['status_name_ast'] !== 'active') {
            session_unset();
            session_destroy();
            session_start($sessionOptions);
            header('Location: /login');
            exit;
        }

        if ($currentAccount['role_name_rol'] !== ($_SESSION['user_role'] ?? '')) {
            $_SESSION['user_role'] = $currentAccount['role_name_rol'];
        }

        $_SESSION['_role_verified_at'] = time();
    }
}

// Enforce TOS acceptance — logged-in users must accept the current version
if (!empty($_SESSION['logged_in'])) {
    $tosWhitelist = ['/tos', '/tos/accept', '/logout', '/login', '/register'];
    $requestUri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

    if (!in_array($requestUri, $tosWhitelist, true)) {
        $currentTos = \App\Models\Tos::getCurrent();

        if ($currentTos !== null) {
            $tosId    = (int) $currentTos['id_tos'];
            $cacheKey = '_tos_accepted_' . $tosId;

            if (!isset($_SESSION[$cacheKey])) {
                $_SESSION[$cacheKey] = \App\Models\Tos::hasUserAccepted(
                    accountId: (int) $_SESSION['user_id'],
                    tosId: $tosId,
                );
            }

            if (!$_SESSION[$cacheKey]) {
                header('Location: /tos');
                exit;
            }
        }
    }
}

// Generate CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate per-request CSP nonce for script-src
define('CSP_NONCE', base64_encode(random_bytes(16)));

// CSP header — nonce-based script-src with strict-dynamic propagation
$cspNonce = CSP_NONCE;
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$cspNonce}' 'strict-dynamic'; style-src 'self'; font-src 'self'; img-src 'self' data: blob:; connect-src 'self' https://challenges.cloudflare.com https://api.stripe.com; frame-src 'self' https://challenges.cloudflare.com https://js.stripe.com https://hooks.stripe.com; frame-ancestors 'none'; object-src 'none'; manifest-src 'self'; worker-src 'self' blob:; base-uri 'self'; form-action 'self'; upgrade-insecure-requests; trusted-types default; require-trusted-types-for 'script'");

// Dynamic cache control (remaining security headers served via .htaccess)
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$privatePrefixes = ['/dashboard', '/profile', '/admin', '/notifications', '/payments', '/disputes', '/incidents', '/waivers', '/handover'];
$isPrivatePage = !empty($_SESSION['logged_in']) && array_any(
    $privatePrefixes,
    static fn(string $prefix): bool => str_starts_with($requestPath, $prefix)
);
header($isPrivatePage
    ? 'Cache-Control: no-cache, private, must-revalidate'
    : 'Cache-Control: no-cache, private');

// Get request method and URI — support _method override for HTML forms
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && !empty($_POST['_method'])) {
    $override = strtoupper($_POST['_method']);
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $override;
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// Strip trailing slash (except root)
if ($uri !== '/') {
    $uri = rtrim($uri, '/');
}

// Match route
$matched = false;

try {
    $candidates = $routesByMethod[$method] ?? [];

    foreach ($candidates as $candidate) {
        $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $candidate['path']);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            [$controllerClass, $action] = $candidate['handler'];

            // Extract named parameters
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Instantiate controller and call action
            $controller = new $controllerClass();
            $controller->$action(...array_values($params));

            $matched = true;
            break;
        }
    }

    if (!$matched) {
        http_response_code(404);
        require BASE_PATH . '/src/Views/errors/404.php';
    }
} catch (\Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    try {
        require BASE_PATH . '/src/Views/errors/500.php';
    } catch (\Throwable) {
        echo '<!doctype html><title>500</title><h1>500 — Internal Server Error</h1>';
    }
}
