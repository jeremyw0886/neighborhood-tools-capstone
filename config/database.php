<?php

// Detect environment based on server name
$isLocal = in_array(
    $_SERVER['SERVER_NAME'] ?? '',
    ['localhost', 'neighborhoodtools', 'neighborhoodtools.local', '127.0.0.1']
);

if ($isLocal) {
    // Local MAMP PRO MySQL configuration (port 8889)
    $dbConfig = [
        'host'     => 'localhost',
        'port'     => 8889,
        'database' => 'neighborhoodtools',
        'username' => 'root',
        'password' => 'root',
    ];
} else {
    // SiteGround production configuration
    $dbConfig = [
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'dbh0ummzt1azm6',
        'username' => 'u1at5eyvxeu68',
        'password' => 'Ethan22122!',
    ];
}

$dbConfig['charset']   = 'utf8mb4';
$dbConfig['collation'] = 'utf8mb4_unicode_ci';
$dbConfig['options']   = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
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
