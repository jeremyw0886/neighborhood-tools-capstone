<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Bookmark;
use App\Models\SearchLog;
use App\Models\Tool;
use App\Models\ZipCode;

class AvailableController extends BaseController
{
    private const int PER_PAGE = 12;

    /**
     * Browse available tools — excludes owner's tools and lent-out tools.
     */
    public function index(): void
    {
        $allowedRadii = [5, 10, 25, 50];

        $term       = trim($_GET['q'] ?? '');
        $categoryId = ($_GET['category'] ?? '') !== '' ? (int) $_GET['category'] : null;
        $maxFee     = ($_GET['max_fee'] ?? '') !== '' ? (float) $_GET['max_fee'] : null;
        $page       = max(1, (int) ($_GET['page'] ?? 1));

        [
            'radius'            => $radius,
            'zip'               => $zip,
            'radiusAutoApplied' => $radiusAutoApplied,
        ] = self::resolveDefaultRadius($_GET, $_SESSION['user_zip'] ?? null, $allowedRadii);

        $zipWarning = '';
        if ($zip !== null) {
            try {
                if (!ZipCode::exists($zip)) {
                    $zipWarning = 'ZIP code ' . $zip . ' is not in our service area. Try a nearby ZIP.';
                }
            } catch (\Throwable $e) {
                error_log('AvailableController::index ZIP check — ' . $e->getMessage());
            }
        }

        try {
            $totalCount = Tool::searchCount(
                term: $term,
                categoryId: $categoryId,
                zip: $zip,
                maxFee: $maxFee,
                radius: $radius,
                availableOnly: true,
            );

            $categories   = Tool::getCategories();
            $browseCounts = Tool::getBrowseableCountsByCategory(availableOnly: true);

            $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $offset = ($page - 1) * self::PER_PAGE;

            $tools = Tool::search(
                term: $term,
                categoryId: $categoryId,
                zip: $zip,
                maxFee: $maxFee,
                limit: self::PER_PAGE,
                offset: $offset,
                radius: $radius,
                availableOnly: true,
            );

            if ($tools !== []) {
                $toolIds      = array_column($tools, 'id_tol');
                $categoryData = Tool::getCategoryDataForTools($toolIds);

                foreach ($tools as &$t) {
                    $data = $categoryData[(int) $t['id_tol']] ?? [];
                    $t['category_name'] = $data['category_name'] ?? null;
                    $t['category_icon'] = $data['category_icon'] ?? null;
                }
                unset($t);
            }

            if ($term !== '') {
                try {
                    SearchLog::insert(
                        term: $term,
                        accountId: !empty($_SESSION['logged_in']) ? (int) $_SESSION['user_id'] : null,
                        ipAddress: $_SERVER['REMOTE_ADDR'] ?? '',
                        sessionId: session_id(),
                    );
                } catch (\Throwable $e) {
                    error_log('AvailableController::index search log — ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            error_log('AvailableController::index — ' . $e->getMessage());
            $tools        = [];
            $totalCount   = 0;
            $categories   = [];
            $browseCounts = [];
        }

        $totalPages ??= (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $filterParams = array_filter([
            'q'        => $term !== '' ? $term : null,
            'category' => $categoryId,
            'zip'      => $zip,
            'radius'   => $radius,
            'max_fee'  => $maxFee,
        ], static fn(mixed $v): bool => $v !== null);

        $sliderMax = 0;
        foreach ($categories as $cat) {
            if (isset($cat['max_rental_fee']) && (float) $cat['max_rental_fee'] > $sliderMax) {
                $sliderMax = (float) $cat['max_rental_fee'];
            }
        }
        $sliderMax   = $sliderMax > 0 ? (int) (ceil($sliderMax / 5) * 5) : 50;
        $sliderValue = $maxFee !== null ? (int) $maxFee : $sliderMax;

        $bookmarkedIds = [];

        if (!empty($_SESSION['logged_in'])) {
            try {
                $bookmarkedIds = Bookmark::getToolIdsForUser((int) $_SESSION['user_id']);
            } catch (\Throwable $e) {
                error_log('AvailableController::index bookmarks — ' . $e->getMessage());
            }
        }

        if ($this->isXhr()) {
            $basePath = '/available';
            extract($this->getSharedData());

            ob_start();
            foreach ($tools as $tool) {
                require BASE_PATH . '/src/Views/partials/tool-card.php';
            }
            $cardsHtml = ob_get_clean();

            ob_start();
            require BASE_PATH . '/src/Views/partials/pagination.php';
            $paginationHtml = ob_get_clean();

            $rangeStart = $totalCount > 0 ? (($page - 1) * self::PER_PAGE) + 1 : 0;
            $rangeEnd   = min($page * self::PER_PAGE, $totalCount);

            $hasNonCategoryFilters = $term !== '' || $zip !== null || $maxFee !== null || $radius !== null;
            $categoryCounts = $hasNonCategoryFilters
                ? Tool::searchCountsByCategory(
                    term: $term,
                    zip: $zip,
                    maxFee: $maxFee,
                    radius: $radius,
                    availableOnly: true,
                )
                : null;

            $response = [
                'success'        => true,
                'html'           => $cardsHtml,
                'paginationHtml' => $paginationHtml,
                'totalCount'     => $totalCount,
                'rangeStart'     => $rangeStart,
                'rangeEnd'       => $rangeEnd,
                'zip'            => $zip,
                'radius'         => $radius,
            ];

            if ($categoryCounts !== null) {
                $response['categoryCounts'] = $categoryCounts;
            }

            $this->jsonResponse(200, $response);
        }

        $this->render('tools/index', [
            'title'         => 'Available Tools — NeighborhoodTools',
            'description'   => 'Browse tools available to borrow right now from your neighbors in the Asheville and Hendersonville areas.',
            'pageCss'       => ['tools.css'],
            'pageJs'        => ['tools.js'],
            'tools'         => $tools,
            'categories'    => $categories,
            'browseCounts'  => $browseCounts,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
            'filterParams'  => $filterParams,
            'term'          => $term,
            'categoryId'    => $categoryId,
            'zip'           => $zip,
            'userZip'       => $_SESSION['user_zip'] ?? null,
            'radius'        => $radius,
            'maxFee'        => $maxFee,
            'sliderMax'     => $sliderMax,
            'sliderValue'   => $sliderValue,
            'bookmarkedIds' => $bookmarkedIds,
            'zipWarning'        => $zipWarning,
            'radiusAutoApplied' => $radiusAutoApplied,
            'bookmarkFlash'     => $this->flash('bookmark_flash'),
            'availableOnly'     => true,
        ]);
    }
}
