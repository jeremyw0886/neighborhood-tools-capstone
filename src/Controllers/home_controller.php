<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Account;
use App\Models\Bookmark;
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

        $currentUserId = !empty($_SESSION['logged_in'])
            ? (int) $_SESSION['user_id']
            : null;

        try {
            $topMembers = Account::getTopMembers(10, $currentUserId);
        } catch (\Exception) {
            $topMembers = [];
        }

        // Location toggle: validate against the two served cities
        $location = trim($_GET['location'] ?? '');
        $validCities = ['Asheville', 'Hendersonville'];

        $selectedCity = array_find($validCities, fn(string $c) => strcasecmp($c, $location) === 0)
            ?? 'Asheville';

        try {
            $nearbyMembers = Account::getNearbyMembers($selectedCity, 10, $currentUserId);
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

        // Fetch bookmarked tool IDs for the active-state icon in tool cards
        $bookmarkedIds = [];

        if (!empty($_SESSION['logged_in'])) {
            try {
                $bookmarkedIds = Bookmark::getToolIdsForUser((int) $_SESSION['user_id']);
            } catch (\Throwable $e) {
                error_log('HomeController::index bookmarks — ' . $e->getMessage());
            }
        }

        $this->render('home/index', [
            'title'            => 'NeighborhoodTools — Share Tools, Build Community',
            'heroPage'         => true,
            'pageCss'          => ['home.css'],
            'pageJs'           => ['home.js'],
            'selectedCity'     => $selectedCity,
            'nearbyMembers'    => $nearbyMembers,
            'isNearbyFallback' => $isNearbyFallback,
            'featuredTools'    => $featuredTools,
            'topMembers'        => $topMembers,
            'friendlyNeighbors' => array_slice($topMembers, 0, 3),
            'bookmarkedIds'    => $bookmarkedIds,
            'bookmarkFlash'    => $this->flash('bookmark_flash'),
        ]);
    }
}
