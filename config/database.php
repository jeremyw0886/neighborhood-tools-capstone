<?php

declare(strict_types=1);

$collation = $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci';

$dbConfig = [
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'port'      => (int) ($_ENV['DB_PORT'] ?? 3306),
    'database'  => $_ENV['DB_DATABASE'] ?? '',
    'username'  => $_ENV['DB_USERNAME'] ?? '',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => $collation,
];

$mysqlInitCmd = defined('Pdo\Mysql::ATTR_INIT_COMMAND')
    ? Pdo\Mysql::ATTR_INIT_COMMAND
    : PDO::MYSQL_ATTR_INIT_COMMAND;

$dbConfig['options'] = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    $mysqlInitCmd                => "SET NAMES utf8mb4 COLLATE {$collation}",
];

$dbConfig['dsn'] = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['database'],
    $dbConfig['charset']
);

return $dbConfig;
