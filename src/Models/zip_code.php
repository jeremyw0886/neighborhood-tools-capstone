<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ZipCode
{
    /**
     * Check whether a ZIP code already exists in the lookup table.
     *
     * Primary-key lookup — O(1) via B-tree index.
     */
    public static function exists(string $zipCode): bool
    {
        $pdo = Database::connection();

        $sql = "SELECT 1 FROM zip_code_zpc WHERE zip_code_zpc = :zip LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':zip', $zipCode);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    /**
     * Ensure a ZIP code exists in the lookup table, geocoding via Google if necessary.
     *
     * Fast path: if the ZIP is already seeded, returns immediately (no API call).
     * Slow path: resolves coordinates via Google Geocoding API, inserts the row,
     * so the FK constraint on account_acc.zip_code_acc is satisfied.
     *
     * @throws \RuntimeException If the API key is missing or geocoding fails
     */
    public static function ensureExists(string $zipCode): void
    {
        if (self::exists($zipCode)) {
            return;
        }

        $apiKey = $_ENV['GOOGLE_GEOCODING_API_KEY'] ?? '';

        if ($apiKey === '') {
            throw new \RuntimeException('Google Geocoding API key is not configured');
        }

        $coords = self::geocode($zipCode, $apiKey);

        if ($coords === null) {
            throw new \RuntimeException("Unable to geocode ZIP code: {$zipCode}");
        }

        self::insert($zipCode, $coords['lat'], $coords['lng']);
    }

    /**
     * Call the Google Geocoding API to resolve a US ZIP code to lat/lng.
     *
     * @return ?array{lat: float, lng: float} Coordinates, or null on any failure
     */
    private static function geocode(string $zipCode, string $apiKey): ?array
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address'    => $zipCode,
            'components' => 'country:US',
            'key'        => $apiKey,
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method'  => 'GET',
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            error_log("ZipCode::geocode — network failure for ZIP {$zipCode}");
            return null;
        }

        $data = json_decode($body, true);

        if ($data === null) {
            error_log("ZipCode::geocode — invalid JSON response for ZIP {$zipCode}");
            return null;
        }

        if ($data['status'] !== 'OK') {
            error_log("ZipCode::geocode — API status '{$data['status']}' for ZIP {$zipCode}"
                . (isset($data['error_message']) ? ": {$data['error_message']}" : ''));
            return null;
        }

        $location = $data['results'][0]['geometry']['location'] ?? null;

        if ($location === null || !isset($location['lat'], $location['lng'])) {
            error_log("ZipCode::geocode — missing geometry in response for ZIP {$zipCode}");
            return null;
        }

        return [
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng'],
        ];
    }

    /**
     * Insert a geocoded ZIP code into the lookup table.
     *
     * Uses INSERT IGNORE to handle race conditions — if two users register
     * with the same new ZIP simultaneously, the second insert is a no-op.
     *
     * Constructs the POINT via ST_GeomFromText('POINT(lng lat)', 4326) to
     * match the seed data convention in the SQL dump.
     */
    private static function insert(string $zipCode, float $lat, float $lng): void
    {
        $pdo = Database::connection();

        $sql = "
            INSERT IGNORE INTO zip_code_zpc (
                zip_code_zpc,
                latitude_zpc,
                longitude_zpc,
                location_point_zpc
            ) VALUES (
                ?, ?, ?,
                ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326, 'axis-order=long-lat')
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$zipCode, $lat, $lng, $lng, $lat]);
    }
}
