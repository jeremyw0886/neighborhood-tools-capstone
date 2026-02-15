<?php

declare(strict_types=1);

// Detect environment based on server name
$isLocal = in_array(
    $_SERVER['SERVER_NAME'] ?? '',
    ['localhost', 'neighborhoodtools', 'neighborhoodtools.local', 'neighborhoodtools.test', '127.0.0.1']
);

if ($isLocal) {
    $isWindows = PHP_OS_FAMILY === 'Windows';

    if ($isWindows) {
        // Local Laragon MySQL configuration (default port, empty password)
        $dbConfig = [
            'host'     => 'localhost',
            'port'     => 3306,
            'database' => 'neighborhoodtools',
            'username' => 'root',
            'password' => '',
        ];
    } else {
        // Local MAMP PRO MySQL configuration (port 8889)
        $dbConfig = [
            'host'     => 'localhost',
            'port'     => 8889,
            'database' => 'neighborhoodtools',
            'username' => 'root',
            'password' => 'root',
        ];
    }
} else {
    // SiteGround production configuration
    $dbConfig = [
        'host'     => $_ENV['DB_HOST'] ?? 'localhost',
        'port'     => $_ENV['DB_PORT'] ?? 3306,
        'database' => $_ENV['DB_DATABASE'] ?? '',
        'username' => $_ENV['DB_USERNAME'] ?? '',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ];
}

$dbConfig['charset']   = 'utf8mb4';
$dbConfig['collation'] = $isLocal ? 'utf8mb4_0900_ai_ci' : 'utf8mb4_unicode_ci';

$mysqlInitCmd = defined('Pdo\Mysql::ATTR_INIT_COMMAND')
    ? Pdo\Mysql::ATTR_INIT_COMMAND
    : PDO::MYSQL_ATTR_INIT_COMMAND;

$dbConfig['options'] = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    $mysqlInitCmd                => "SET NAMES utf8mb4 COLLATE " . ($isLocal ? 'utf8mb4_0900_ai_ci' : 'utf8mb4_unicode_ci'),
];

// Build DSN
$dbConfig['dsn'] = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['database'],
    $dbConfig['charset']
);

return $dbConfig;
