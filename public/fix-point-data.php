<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = App\Core\Database::connection();

// Show current (broken) state for a sample ZIP
$stmt = $pdo->query("
    SELECT zip_code_zpc, ST_Latitude(location_point_zpc) AS st_lat, ST_Longitude(location_point_zpc) AS st_lng
    FROM zip_code_zpc WHERE zip_code_zpc = '28801'
");
$r = $stmt->fetch();
echo "BEFORE fix — 28801: ST_Lat={$r['st_lat']}  ST_Lng={$r['st_lng']}\n";
echo "(Lat should be ~35.595, Lng should be ~-82.556)\n\n";

// Recalculate ALL POINTs from the correct DECIMAL columns
// axis-order=long-lat tells MySQL to parse the WKT as POINT(longitude latitude)
$affected = $pdo->exec("
    UPDATE zip_code_zpc
    SET location_point_zpc = ST_GeomFromText(
        CONCAT('POINT(', longitude_zpc, ' ', latitude_zpc, ')'),
        4326,
        'axis-order=long-lat'
    )
");

echo "Fixed {$affected} rows.\n\n";

// Verify after fix
$stmt = $pdo->query("
    SELECT zip_code_zpc, ST_Latitude(location_point_zpc) AS st_lat, ST_Longitude(location_point_zpc) AS st_lng
    FROM zip_code_zpc WHERE zip_code_zpc IN ('28801', '90210') ORDER BY zip_code_zpc
");
foreach ($stmt->fetchAll() as $r) {
    echo "AFTER fix — {$r['zip_code_zpc']}: ST_Lat={$r['st_lat']}  ST_Lng={$r['st_lng']}\n";
}

// Distance check
echo "\n";
$stmt = $pdo->query("
    SELECT ROUND(
        ST_Distance_Sphere(a.location_point_zpc, b.location_point_zpc) / 1609.344
    ) AS distance_miles
    FROM zip_code_zpc a, zip_code_zpc b
    WHERE a.zip_code_zpc = '28801' AND b.zip_code_zpc = '90210'
");
$r = $stmt->fetch();
echo "ST_Distance_Sphere: 28801 ↔ 90210 = {$r['distance_miles']} miles\n";
echo "(Expected ~2,100 miles)\n";
