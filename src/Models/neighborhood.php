<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Neighborhood
{
    /** Maximum distance (miles) for auto-matching a neighborhood from an address. */
    private const float MAX_MATCH_DISTANCE = 10.0;

    /**
     * Aggregate platform-wide member and tool totals from materialized neighborhood data.
     *
     * @return array{totalMembers: int, activeMembers: int, availableTools: int}
     */
    public static function getPlatformTotals(): array
    {
        $pdo = Database::connection();

        $row = $pdo->query(
            'SELECT COALESCE(SUM(total_members), 0)   AS total_members,
                    COALESCE(SUM(active_members), 0)   AS active_members,
                    COALESCE(SUM(available_tools), 0)   AS available_tools
               FROM neighborhood_summary_fast_v'
        )->fetch(PDO::FETCH_ASSOC);

        return [
            'totalMembers'   => (int) $row['total_members'],
            'activeMembers'  => (int) $row['active_members'],
            'availableTools' => (int) $row['available_tools'],
        ];
    }

    /** Meters per mile — used for ST_Distance_Sphere conversion. */
    private const float METERS_PER_MILE = 1609.344;
    /**
     * Fetch all neighborhoods grouped by city, ordered alphabetically.
     *
     * Used by the registration form's neighborhood dropdown.
     *
     * @return array<int, array{id_nbh: int, neighborhood_name_nbh: string, city_name_nbh: string}>
     */
    public static function allGroupedByCity(): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT id_nbh, neighborhood_name_nbh, city_name_nbh
            FROM neighborhood_nbh
            ORDER BY city_name_nbh, neighborhood_name_nbh
        ";

        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Fetch distinct city names for the location toggle radio buttons.
     *
     * @return array<int, array{city: string}>
     */
    public static function getCities(): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT DISTINCT city_name_nbh AS city
            FROM neighborhood_nbh
            ORDER BY city_name_nbh
        ";

        $stmt = $pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Resolve the nearest neighborhood from a street address via Google Geocoding.
     *
     * Geocodes the full address, then finds the closest neighborhood within
     * MAX_MATCH_DISTANCE miles using ST_Distance_Sphere. Returns null if the
     * API key is missing, geocoding fails, or no neighborhood is close enough.
     */
    public static function resolveFromAddress(string $address, string $zipCode): ?int
    {
        $apiKey = $_ENV['GOOGLE_GEOCODING_API_KEY'] ?? '';

        if ($apiKey === '') {
            return null;
        }

        $coords = self::geocodeAddress($address . ', ' . $zipCode, $apiKey);

        if ($coords === null) {
            return null;
        }

        return self::findNearestId($coords['lat'], $coords['lng']);
    }

    /**
     * Find the nearest neighborhood to the given coordinates.
     *
     * Uses the spatial index on location_point_nbh for efficient lookup.
     * Returns null if no neighborhood is within MAX_MATCH_DISTANCE miles.
     */
    private static function findNearestId(float $lat, float $lng): ?int
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                id_nbh,
                ST_Distance_Sphere(
                    location_point_nbh,
                    ST_GeomFromText(:point, 4326, 'axis-order=long-lat')
                ) / :meters_per_mile AS distance_miles
            FROM neighborhood_nbh
            HAVING distance_miles <= :max_distance
            ORDER BY distance_miles ASC
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':point', sprintf('POINT(%F %F)', $lng, $lat));
        $stmt->bindValue(':meters_per_mile', self::METERS_PER_MILE);
        $stmt->bindValue(':max_distance', self::MAX_MATCH_DISTANCE);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row !== false ? (int) $row['id_nbh'] : null;
    }

    /**
     * Geocode an address string via the Google Geocoding API.
     *
     * @return ?array{lat: float, lng: float}
     */
    private static function geocodeAddress(string $address, string $apiKey): ?array
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address'    => $address,
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
            error_log("Neighborhood::geocodeAddress — network failure for: {$address}");
            return null;
        }

        $data = json_decode($body, true);

        if ($data === null || ($data['status'] ?? '') !== 'OK') {
            error_log("Neighborhood::geocodeAddress — API status '" . ($data['status'] ?? 'null') . "' for: {$address}");
            return null;
        }

        $location = $data['results'][0]['geometry']['location'] ?? null;

        if ($location === null || !isset($location['lat'], $location['lng'])) {
            return null;
        }

        return [
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng'],
        ];
    }
}
