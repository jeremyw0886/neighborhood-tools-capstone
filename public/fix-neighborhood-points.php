<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = App\Core\Database::connection();

// Show current state
$stmt = $pdo->query("
    SELECT neighborhood_name_nbh, latitude_nbh, ST_Latitude(location_point_nbh) AS st_lat
    FROM neighborhood_nbh LIMIT 1
");
$r = $stmt->fetch();
echo "BEFORE fix — {$r['neighborhood_name_nbh']}: DECIMAL lat={$r['latitude_nbh']}  POINT ST_Lat={$r['st_lat']}\n";

$needs_fix = abs((float)$r['latitude_nbh'] - (float)$r['st_lat']) > 0.001;

if (!$needs_fix) {
    echo "POINTs already correct — no fix needed.\n";
    exit(0);
}

// Fix all neighborhood POINTs from the correct DECIMAL columns
$affected = $pdo->exec("
    UPDATE neighborhood_nbh
    SET location_point_nbh = ST_GeomFromText(
        CONCAT('POINT(', longitude_nbh, ' ', latitude_nbh, ')'),
        4326,
        'axis-order=long-lat'
    )
");

echo "Fixed {$affected} rows.\n\n";

// Verify
$stmt = $pdo->query("
    SELECT neighborhood_name_nbh, city_name_nbh, latitude_nbh, longitude_nbh,
           ST_Latitude(location_point_nbh) AS st_lat, ST_Longitude(location_point_nbh) AS st_lng
    FROM neighborhood_nbh ORDER BY city_name_nbh LIMIT 3
");
foreach ($stmt->fetchAll() as $r) {
    echo "AFTER fix — {$r['neighborhood_name_nbh']} ({$r['city_name_nbh']}): ST_Lat={$r['st_lat']}  ST_Lng={$r['st_lng']}\n";
}
