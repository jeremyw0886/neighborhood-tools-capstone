<?php

declare(strict_types=1);

$collation = $_ENV['DB_COLLATION'] ?? 'utf8mb4_0900_ai_ci';

$dbConfig = [
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => (int) ($_ENV['DB_PORT'] ?? 3306),
    'database'  => $_ENV['DB_DATABASE'] ?? '',
    'username'  => $_ENV['DB_USERNAME'] ?? '',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => $collation,
];

$dbConfig['options'] = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    Pdo\Mysql::ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE {$collation}, time_zone = 'America/New_York'",
];

$dbConfig['dsn'] = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['database'],
    $dbConfig['charset']
);

return $dbConfig;
