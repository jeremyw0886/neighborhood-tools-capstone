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
    : (static function (): string {
        $file = BASE_PATH . '/config/asset-version.php';
        return file_exists($file) ? require $file : '1.0.0';
    })()
);

// Load configuration
$appConfig = require BASE_PATH . '/config/app.php';
$dbConfig  = require BASE_PATH . '/config/database.php';
$routes    = require BASE_PATH . '/config/routes.php';

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Start session with secure cookie settings
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure'   => $isHttps,
]);

// Generate CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; font-src 'self'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip trailing slash (except root)
if ($uri !== '/') {
    $uri = rtrim($uri, '/');
}

// Match route
$matched = false;

try {
    foreach ($routes as $route => $handler) {
        [$routeMethod, $routePath] = explode(' ', $route, 2);

        if ($method !== $routeMethod) {
            continue;
        }

        // Convert route placeholders {param} to regex
        $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            [$controllerClass, $action] = $handler;

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
    require BASE_PATH . '/src/Views/errors/500.php';
}
