<?php

declare(strict_types=1);

/**
 * Quick integration test for ZipCode::ensureExists().
 * Run from project root: php local_testing/test-geocoding.php
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Confirm API key is loaded (show first/last 4 chars only)
$key = $_ENV['GOOGLE_GEOCODING_API_KEY'] ?? '';
if ($key === '') {
    echo "FAIL: GOOGLE_GEOCODING_API_KEY is empty or missing in .env\n";
    exit(1);
}
echo "API key loaded: " . substr($key, 0, 4) . '...' . substr($key, -4) . "\n\n";

use App\Models\ZipCode;

// Test 1: Seeded ZIP should already exist (no API call)
echo "Test 1 — ZipCode::exists('28801') [seeded ZIP]\n";
$exists = ZipCode::exists('28801');
echo "  Result: " . ($exists ? 'EXISTS (pass)' : 'NOT FOUND (fail)') . "\n\n";

// Test 2: ensureExists with a new ZIP (triggers geocoding)
$testZip = '90210';
echo "Test 2 — ZipCode::ensureExists('{$testZip}') [geocode + insert]\n";
try {
    ZipCode::ensureExists($testZip);
    echo "  Result: SUCCESS (pass)\n";
} catch (\Throwable $e) {
    echo "  Result: FAILED — " . $e->getMessage() . "\n";
}

// Test 3: Verify the row landed in the database
echo "\nTest 3 — Verify '{$testZip}' in zip_code_zpc\n";
$pdo = App\Core\Database::connection();
$stmt = $pdo->prepare("
    SELECT zip_code_zpc, latitude_zpc, longitude_zpc, ST_AsText(location_point_zpc) AS point_wkt
    FROM zip_code_zpc
    WHERE zip_code_zpc = :zip
");
$stmt->bindValue(':zip', $testZip);
$stmt->execute();
$row = $stmt->fetch();

if ($row) {
    echo "  zip:  {$row['zip_code_zpc']}\n";
    echo "  lat:  {$row['latitude_zpc']}\n";
    echo "  lng:  {$row['longitude_zpc']}\n";
    echo "  point: {$row['point_wkt']}\n";
    echo "  Result: VERIFIED (pass)\n";
} else {
    echo "  Result: ROW NOT FOUND (fail)\n";
}

// Test 4: Invalid ZIP should fail gracefully
echo "\nTest 4 — ZipCode::ensureExists('00000') [invalid ZIP]\n";
try {
    ZipCode::ensureExists('00000');
    echo "  Result: UNEXPECTED SUCCESS (fail — should have thrown)\n";
} catch (\Throwable $e) {
    echo "  Result: Threw as expected — " . $e->getMessage() . " (pass)\n";
}

echo "\nDone.\n";
