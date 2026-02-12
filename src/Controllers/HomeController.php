<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Account;
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

        // Location toggle: validate against the two served cities
        $location = trim($_GET['location'] ?? '');
        $validCities = ['Asheville', 'Hendersonville'];

        $selectedCity = array_find($validCities, fn(string $c) => strcasecmp($c, $location) === 0)
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
            'pageCss'          => ['home.css'],
            'selectedCity'     => $selectedCity,
            'nearbyMembers'    => $nearbyMembers,
            'isNearbyFallback' => $isNearbyFallback,
            'featuredTools'    => $featuredTools,
            'topMembers'       => $topMembers,
        ]);
    }
}
