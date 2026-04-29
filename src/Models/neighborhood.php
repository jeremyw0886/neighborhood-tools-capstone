<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\FileCache;
use PDO;

class Neighborhood
{
    /** Maximum distance (miles) for auto-matching a neighborhood from an address. */
    private const float MAX_MATCH_DISTANCE = 10.0;

    /**
     * Live platform-wide totals for the hero trust signals.
     *
     * `totalMembers` counts every non-deleted account (active + pending +
     * suspended) so the admin dashboard's "X active out of Y" framing is
     * meaningful; `activeMembers` is the active-only subset that the home
     * page hero shows directly.
     *
     * @return array{totalMembers: int, activeMembers: int, availableTools: int, completedBorrows: int}
     */
    public static function getPlatformTotals(): array
    {
        $pdo = Database::connection();

        $memberCounts = $pdo->query(
            "SELECT
                SUM(CASE WHEN ast.status_name_ast <> 'deleted' THEN 1 ELSE 0 END) AS total_members,
                SUM(CASE WHEN ast.status_name_ast =  'active'  THEN 1 ELSE 0 END) AS active_members
             FROM account_acc a
             JOIN account_status_ast ast ON a.id_ast_acc = ast.id_ast"
        )->fetch();

        $availableTools = (int) $pdo->query(
            'SELECT COUNT(*) FROM available_tool_v'
        )->fetchColumn();

        $completedBorrows = (int) $pdo->query(
            "SELECT COUNT(*) FROM borrow_bor
             WHERE id_bst_bor = (SELECT id_bst FROM borrow_status_bst
                                 WHERE status_name_bst = 'returned')
               AND returned_at_bor >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )->fetchColumn();

        return [
            'totalMembers'     => (int) ($memberCounts['total_members']  ?? 0),
            'activeMembers'    => (int) ($memberCounts['active_members'] ?? 0),
            'availableTools'   => $availableTools,
            'completedBorrows' => $completedBorrows,
        ];
    }

    /**
     * Shared FileCache-backed wrapper around getPlatformTotals().
     *
     * The hero trust signals are identical for every visitor, so a single
     * shared cache entry replaces the previous per-session copy and lets a
     * cold first-page-render bot pay the query cost at most once per $ttl.
     *
     * @param  int $ttl  Cache lifetime in seconds
     * @return array{totalMembers: int, activeMembers: int, availableTools: int, completedBorrows: int}
     */
    public static function getCachedPlatformTotals(int $ttl = 60): array
    {
        return FileCache::remember(
            'neighborhood:platform-totals',
            $ttl,
            self::getPlatformTotals(...),
        );
    }

    private const array SUMMARY_SORT_FIELDS = [
        'neighborhood_name_nbh',
        'active_members',
        'available_tools',
        'active_borrows',
        'upcoming_events',
    ];

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
    public static function getSummaryList(int $limit, int $offset, string $sort = 'neighborhood_name_nbh', string $dir = 'ASC'): array
    {
        $sortCol  = in_array($sort, self::SUMMARY_SORT_FIELDS, true) ? $sort : 'neighborhood_name_nbh';
        $sortDir  = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            "SELECT id_nbh, neighborhood_name_nbh, city_name_nbh,
                    state_code_sta, total_members, active_members,
                    verified_members, total_tools, available_tools,
                    active_borrows, completed_borrows_30d,
                    upcoming_events, zip_codes, refreshed_at
               FROM neighborhood_summary_fast_v
              ORDER BY {$sortCol} {$sortDir}, neighborhood_name_nbh ASC
              LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search neighborhoods by name, city, or state for admin global search.
     *
     * @return array<int, array{id_nbh: int, neighborhood_name_nbh: string, city_name_nbh: string, state_code_sta: string, active_members: int}>
     */
    public static function adminSearch(string $term, int $limit = 5): array
    {
        $pdo = Database::connection();

        $sql = "
            SELECT
                id_nbh,
                neighborhood_name_nbh,
                city_name_nbh,
                state_code_sta,
                active_members
            FROM neighborhood_summary_fast_v
            WHERE neighborhood_name_nbh LIKE CONCAT('%', :term1, '%')
               OR city_name_nbh         LIKE CONCAT('%', :term2, '%')
               OR state_code_sta        LIKE CONCAT('%', :term3, '%')
            ORDER BY neighborhood_name_nbh ASC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $escaped = Database::escapeLike($term);
        $stmt->bindValue(':term1', $escaped);
        $stmt->bindValue(':term2', $escaped);
        $stmt->bindValue(':term3', $escaped);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
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
     * Fetch neighborhoods linked to a ZIP code via the junction table.
     *
     * @return array<int, array{id_nbh: int, neighborhood_name_nbh: string, city_name_nbh: string, is_primary: int}>
     */
    public static function getByZipCode(string $zip): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT n.id_nbh,
                   n.neighborhood_name_nbh,
                   n.city_name_nbh,
                   j.is_primary_nbhzpc AS is_primary
            FROM neighborhood_zip_nbhzpc j
            JOIN neighborhood_nbh n ON n.id_nbh = j.id_nbh_nbhzpc
            WHERE j.zip_code_nbhzpc = :zip
            ORDER BY j.is_primary_nbhzpc DESC, n.neighborhood_name_nbh
        ");
        $stmt->bindValue(':zip', $zip);
        $stmt->execute();

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
