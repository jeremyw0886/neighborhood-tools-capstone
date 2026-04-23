<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\ImageProcessor;
use App\Models\Account;
use App\Models\Bookmark;
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

        $preloadImage = null;
        $firstTool    = $featuredTools[0] ?? null;

        if (!empty($firstTool['primary_image'])) {
            $variants = ImageProcessor::getAvailableVariants(
                $firstTool['primary_image'],
                $firstTool['primary_width'] ?? null,
                ImageProcessor::VARIANT_WIDTHS,
            );
            $srcsets = ImageProcessor::buildSrcset($variants);
            $isWebp  = str_ends_with($firstTool['primary_image'], '.webp');

            [$type, $srcset] = match (true) {
                !$isWebp && $srcsets['avifSrcset'] !== '' => ['image/avif', $srcsets['avifSrcset']],
                !$isWebp && $srcsets['webpSrcset'] !== '' => ['image/webp', $srcsets['webpSrcset']],
                default                                   => ['',           $srcsets['srcset']],
            };

            if ($srcset !== '') {
                $preloadImage = [
                    'type'   => $type,
                    'srcset' => $srcset,
                    'sizes'  => '(max-width: 600px) calc(100vw - 3rem), (max-width: 900px) calc(50vw - 2.25rem), 270px',
                ];
            }
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
            $nearbyMembers = Account::getCachedNearbyMembers($selectedCity, 10, $currentUserId);
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

        try {
            $platformStats = Neighborhood::getCachedPlatformTotals();
        } catch (\Exception) {
            $platformStats = ['totalMembers' => 0, 'activeMembers' => 0, 'availableTools' => 0, 'completedBorrows' => 0];
        }

        $this->render('home/index', [
            'title'            => 'NeighborhoodTools — Share Tools, Build Community',
            'description'      => 'Borrow tools from your neighbors and lend yours when you\'re not using them. Join your local tool-sharing community today.',
            'heroPage'         => true,
            'criticalKey'      => 'home',
            'preloadImage'     => $preloadImage,
            'pageCss'          => ['home.css'],
            'pageJs'           => ['home.js', 'tool-preview.js'],
            'platformStats'    => $platformStats,
            'selectedCity'     => $selectedCity,
            'nearbyMembers'    => $nearbyMembers,
            'isNearbyFallback' => $isNearbyFallback,
            'featuredTools'    => $featuredTools,
            'friendlyNeighbors' => array_slice($topMembers, 0, 3),
            'bookmarkedIds'    => $bookmarkedIds,
            'bookmarkFlash'    => $this->flash('bookmark_flash'),
        ]);
    }
}
