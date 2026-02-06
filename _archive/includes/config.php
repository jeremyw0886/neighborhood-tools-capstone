<?php
// Detect environment based on server name
$is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', 'neighborhoodtools', 'neighborhoodtools.local', '127.0.0.1']);

if ($is_local) {
// Local MAMP PRO MySQL configuration (port 8889)
    $host = 'localhost';
    $port = 8889;
    $dbname = 'neighborhoodtools';
    $username = 'root';
    $password = 'root';
} else {
    // SiteGround production configuration
    $host = 'localhost';
    $dbname = 'dbh0ummzt1azm6';
    $username = 'u1at5eyvxeu68';
    $password = 'Ethan22122!';
}

// Database connection
try {
    if (isset($port)) {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    } else {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    }
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}