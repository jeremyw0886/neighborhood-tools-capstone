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

    /**
     * Paginated list of neighborhood summaries from the materialized cache.
     *
     * @return array<int, array{id_nbh: int, neighborhood_name_nbh: string,
     *               city_name_nbh: string, state_code_sta: string,
     *               total_members: int, active_members: int, verified_members: int,
     *               total_tools: int, available_tools: int, active_borrows: int,
     *               completed_borrows_30d: int, upcoming_events: int,
     *               zip_codes: ?string, refreshed_at: string}>
     */
    public static function getSummaryList(int $limit, int $offset): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id_nbh, neighborhood_name_nbh, city_name_nbh,
                    state_code_sta, total_members, active_members,
                    verified_members, total_tools, available_tools,
                    active_borrows, completed_borrows_30d,
                    upcoming_events, zip_codes, refreshed_at
               FROM neighborhood_summary_fast_v
              ORDER BY city_name_nbh, neighborhood_name_nbh
              LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total neighborhood count for pagination.
     */
    public static function getSummaryCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query(
            'SELECT COUNT(*) FROM neighborhood_summary_fast_v'
        )->fetchColumn();
    }

    /**
     * Single neighborhood with live (non-cached) statistics.
     *
     * Uses the real-time view for detail pages where accuracy matters
     * more than speed.
     *
     * @return ?array{id_nbh: int, neighborhood_name_nbh: string,
     *               city_name_nbh: string, state_code_sta: string,
     *               state_name_sta: string, latitude_nbh: string,
     *               longitude_nbh: string, total_members: int,
     *               active_members: int, verified_members: int,
     *               total_tools: int, available_tools: int,
     *               active_borrows: int, completed_borrows_30d: int,
     *               upcoming_events: int, zip_codes: ?string}
     */
    public static function findSummaryById(int $id): ?array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id_nbh, neighborhood_name_nbh, city_name_nbh,
                    state_code_sta, state_name_sta,
                    latitude_nbh, longitude_nbh,
                    total_members, active_members, verified_members,
                    total_tools, available_tools, active_borrows,
                    completed_borrows_30d, upcoming_events, zip_codes
               FROM neighborhood_summary_v
              WHERE id_nbh = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
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
