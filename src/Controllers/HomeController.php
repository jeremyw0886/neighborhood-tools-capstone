<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Account;
use App\Models\Neighborhood;
use App\Models\Tool;

class HomeController extends BaseController
{
    public function index(): void
    {
        try {
            $featuredTools = Tool::getFeatured(6);
        } catch (\Exception) {
            $featuredTools = [];
        }

        try {
            $topMembers = Account::getTopMembers(3);
        } catch (\Exception) {
            $topMembers = [];
        }

        // Location toggle: read query param, validate against actual cities
        $location = trim($_GET['location'] ?? '');
        $cities = Neighborhood::getCities();
        $cityNames = array_column($cities, 'city');

        $selectedCity = array_find($cityNames, fn(string $c) => strcasecmp($c, $location) === 0)
            ?? 'Asheville';

        try {
            $nearbyMembers = Account::getNearbyMembers($selectedCity, 10);
            $isNearbyFallback = empty($nearbyMembers);
        } catch (\Exception $e) {
            error_log('getNearbyMembers failed: ' . $e->getMessage());
            $nearbyMembers = [];
            $isNearbyFallback = true;
        }

        // Fallback: show top members if spatial query fails or returns empty
        if ($isNearbyFallback) {
            $nearbyMembers = $topMembers;
        }

        $this->render('home/index', [
            'title'            => 'NeighborhoodTools — Share Tools, Build Community',
            'heroPage'         => true,
            'cities'           => $cities,
            'selectedCity'     => $selectedCity,
            'nearbyMembers'    => $nearbyMembers,
            'isNearbyFallback' => $isNearbyFallback,
            'featuredTools'    => $featuredTools,
            'topMembers'       => $topMembers,
        ]);
    }
}
