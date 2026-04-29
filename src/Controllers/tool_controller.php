<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\ImageProcessor;
use App\Models\AvailabilityBlock;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\SearchLog;
use App\Models\Tool;
use App\Models\ZipCode;

class ToolController extends BaseController
{
    /** Results per page — divisible by 2, 3, and 4 for auto-fill grid columns. */
    private const int PER_PAGE = 12;

    /**
     * Browse tools with search, filters, and pagination.
     *
     * Accepts GET params from the hero search form (q) and the browse page's
     * own filter bar (q, category, zip, max_fee, page). All active filters
     * are preserved across pagination and filter changes.
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
                error_log('ToolController::index ZIP check — ' . $e->getMessage());
            }
        }

        try {
            $totalCount = Tool::searchCount(
                term: $term,
                categoryId: $categoryId,
                zip: $zip,
                maxFee: $maxFee,
                radius: $radius,
            );

            $categories   = Tool::getCategories();
            $browseCounts = Tool::getBrowseableCountsByCategory();

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
            );

            if ($tools !== []) {
                $toolIds     = array_column($tools, 'id_tol');
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
                    error_log('ToolController::index search log — ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            error_log('ToolController::index — ' . $e->getMessage());
            $tools        = [];
            $totalCount   = 0;
            $categories   = [];
            $browseCounts = [];
        }

        $totalPages ??= (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        // Build query string for pagination links (preserves all active filters)
        $filterParams = array_filter([
            'q'        => $term !== '' ? $term : null,
            'category' => $categoryId,
            'zip'      => $zip,
            'radius'   => $radius,
            'max_fee'  => $maxFee,
        ], static fn(mixed $v): bool => $v !== null);

        // Compute slider ceiling from the highest rental fee across categories
        $sliderMax = 0;
        foreach ($categories as $cat) {
            if (isset($cat['max_rental_fee']) && (float) $cat['max_rental_fee'] > $sliderMax) {
                $sliderMax = (float) $cat['max_rental_fee'];
            }
        }
        $sliderMax   = $sliderMax > 0 ? (int) (ceil($sliderMax / 5) * 5) : 50;
        $sliderValue = $maxFee !== null ? (int) $maxFee : $sliderMax;

        // Fetch bookmarked tool IDs for the active-state icon in tool cards
        $bookmarkedIds = [];

        if (!empty($_SESSION['logged_in'])) {
            try {
                $bookmarkedIds = Bookmark::getToolIdsForUser((int) $_SESSION['user_id']);
            } catch (\Throwable $e) {
                error_log('ToolController::index bookmarks — ' . $e->getMessage());
            }
        }

        if ($this->isXhr()) {
            $basePath = '/tools';
            $shared   = $this->getSharedData();
            extract($shared);

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
            'title'         => 'Browse Tools — NeighborhoodTools',
            'description'   => 'Search and browse available tools to borrow from your neighbors in the Asheville and Hendersonville areas.',
            'pageCss'       => ['tools.css'],
            'pageJs'        => ['image-crop.js', 'tools.js', 'tool-preview.js'],
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
            'cardSizes'         => '(max-width: 520px) calc(100vw - 3rem), 280px',
        ]);
    }

    /**
     * Return JSON array of tool names matching a partial query.
     */
    public function suggest(): never
    {
        $term = trim($_GET['q'] ?? '');

        if (mb_strlen($term) < 2) {
            $this->jsonResponse(200, []);
        }

        $availableOnly = ($_GET['available'] ?? '') === '1';

        $this->jsonResponse(200, Tool::suggestNames($term, availableOnly: $availableOnly));
    }

    /**
     * Show the authenticated user's bookmarked tools.
     *
     * Paginates bookmarks newest-first using the Bookmark model,
     * which queries user_bookmarks_v. Results are compatible with
     * the tool-card partial via column aliasing.
     */
    public function bookmarks(): void
    {
        $this->requireAuth();

        $userId = (int) $_SESSION['user_id'];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        try {
            $bookmarks  = Bookmark::getForUser($userId, self::PER_PAGE, $offset);
            $totalCount = Bookmark::getCountForUser($userId);
        } catch (\Throwable $e) {
            error_log('ToolController::bookmarks — ' . $e->getMessage());
            $bookmarks  = [];
            $totalCount = 0;
        }

        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        // Every tool on this page is bookmarked — derive IDs from fetched results
        $bookmarkedIds = array_column($bookmarks, 'id_tol');

        $this->renderDashboard('bookmarks', [
            'title'         => 'My Bookmarks — NeighborhoodTools',
            'description'   => 'Your saved tools — NeighborhoodTools',
            'pageCss'       => ['tools.css', 'dashboard.css'],
            'pageJs'        => ['dashboard.js'],
            'bookmarks'     => $bookmarks,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
            'bookmarkedIds' => $bookmarkedIds,
            'bookmarkFlash' => $this->flash('bookmark_flash'),
            'cardSizes'     => '(max-width: 520px) calc(100vw - 3rem), 280px',
        ]);
    }

    /**
     * Show a single tool's detail page.
     *
     * The route passes {id} as a string; cast to int for the model query.
     */
    public function show(string $id): void
    {
        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::show — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        $justSaved = !empty($_SESSION['tool_saved']);
        unset($_SESSION['tool_saved']);

        if (!$justSaved && !empty($_SESSION['logged_in']) && (int) $tool['owner_id'] === (int) $_SESSION['user_id']) {
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        $isBookmarked = false;

        if (!empty($_SESSION['logged_in'])) {
            try {
                $isBookmarked = Bookmark::isBookmarked((int) $_SESSION['user_id'], $toolId);
            } catch (\Throwable $e) {
                error_log('ToolController::show bookmark check — ' . $e->getMessage());
            }
        }

        $isOwner = !empty($_SESSION['logged_in']) && (int) $tool['owner_id'] === (int) $_SESSION['user_id'];

        $images = [];

        try {
            $images = Tool::getImages($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::show images — ' . $e->getMessage());
        }

        $this->render('tools/show', [
            'title'            => $tool['tool_name_tol'] . ' — NeighborhoodTools',
            'pageCss'          => ['tools.css'],
            'pageJs'           => ['image-crop.js', 'tools.js'],
            'tool'             => $tool,
            'images'           => $images,
            'isBookmarked'     => $isBookmarked,
            'isOwner'          => $isOwner,
            'borrowErrors'     => $this->flash('borrow_errors', []),
            'borrowOld'        => $this->flash('borrow_old', []),
            'bookmarkFlash'    => $this->flash('bookmark_flash'),
        ]);
    }

    /**
     * Show the tool listing form.
     *
     * Requires authentication — guests are redirected to login.
     * Recovers flash data (errors + old input) after a failed store() attempt.
     */
    public function create(): void
    {
        $this->requireAuth();

        $categories = [];
        $fuelTypes  = [];

        try {
            $categories = Category::getList();
            $fuelTypes  = Tool::getFuelTypes();
        } catch (\Throwable $e) {
            error_log('ToolController::create — ' . $e->getMessage());
        }

        $errors = $_SESSION['tool_errors'] ?? [];
        $old    = $_SESSION['tool_old'] ?? [];
        unset($_SESSION['tool_errors'], $_SESSION['tool_old']);

        $this->renderDashboard('list-tool', [
            'title'            => 'List a Tool — NeighborhoodTools',
            'description'      => 'List a tool for your neighbors to borrow — NeighborhoodTools',
            'pageCss'          => ['tools.css', 'dashboard.css'],
            'pageJs'           => ['image-crop.js', 'tools.js', 'dashboard.js'],
            'categories'       => $categories,
            'fuelTypes'        => $fuelTypes,
            'errors'           => $errors,
            'old'              => $old,
        ]);
    }

    /**
     * Show the tool edit form, pre-filled with current values.
     *
     * Requires authentication and owner-only access — only the tool's
     * owner may edit it. Recovers flash data from a failed update()
     * attempt so the user sees their last-entered values.
     */
    public function edit(string $id): void
    {
        $this->requireAuth();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::edit — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        // Owner-only access
        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            $this->abort(403);
        }

        $categories = [];
        $fuelTypes  = [];

        try {
            $categories = Category::getList();
            $fuelTypes  = Tool::getFuelTypes();
        } catch (\Throwable $e) {
            error_log('ToolController::edit categories — ' . $e->getMessage());
        }

        $currentCategoryId = null;

        try {
            $currentCategoryId = Tool::getCategoryId($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::edit categoryId — ' . $e->getMessage());
        }

        $errors = $_SESSION['edit_tool_errors'] ?? [];
        $old    = $_SESSION['edit_tool_old'] ?? [];
        unset($_SESSION['edit_tool_errors'], $_SESSION['edit_tool_old']);

        $images = [];

        try {
            $images = Tool::getImages($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::edit images — ' . $e->getMessage());
        }

        $this->renderDashboard('edit-tool', [
            'title'             => 'Edit ' . $tool['tool_name_tol'] . ' — NeighborhoodTools',
            'description'       => 'Edit your tool listing — NeighborhoodTools',
            'pageCss'           => ['tools.css', 'dashboard.css'],
            'pageJs'            => ['image-crop.js', 'tools.js', 'dashboard.js'],
            'tool'              => $tool,
            'images'            => $images,
            'categories'        => $categories,
            'fuelTypes'         => $fuelTypes,
            'currentCategoryId' => $currentCategoryId,
            'errors'            => $errors,
            'old'               => $old,
        ]);
    }

    /**
     * Handle tool edit form submission.
     *
     * Validates ownership and input, then persists field changes via
     * Tool::update(). Image management is handled by dedicated AJAX endpoints.
     */
    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::update fetch — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            $this->abort(403);
        }

        $toolName     = trim($_POST['tool_name'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $categoryId   = (int) ($_POST['category_id'] ?? 0);
        $rentalFee    = $_POST['rental_fee'] ?? '';
        $condition    = trim($_POST['condition'] ?? '');
        $loanDuration = $_POST['loan_duration'] ?? '';
        $usesFuel     = !empty($_POST['uses_fuel']);
        $fuelType     = trim($_POST['fuel_type'] ?? '');

        $errors = $this->validateToolListing($toolName, $categoryId, $rentalFee, $condition, $loanDuration, $usesFuel, $fuelType);

        $oldInput = [
            'tool_name'     => $toolName,
            'description'   => $description,
            'category_id'   => $categoryId,
            'rental_fee'    => $rentalFee,
            'condition'     => $condition,
            'loan_duration' => $loanDuration,
            'uses_fuel'     => $usesFuel,
            'fuel_type'     => $fuelType,
        ];

        if ($errors !== []) {
            $_SESSION['edit_tool_errors'] = $errors;
            $_SESSION['edit_tool_old'] = $oldInput;
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        try {
            Tool::update($toolId, [
                'tool_name'     => $toolName,
                'description'   => $description !== '' ? $description : null,
                'rental_fee'    => (float) $rentalFee,
                'condition'     => $condition,
                'loan_duration' => $loanDuration !== '' ? (int) $loanDuration * 24 : null,
                'fuel_type'     => $usesFuel && $fuelType !== '' ? $fuelType : null,
                'category_id'   => $categoryId,
            ]);

            $_SESSION['tool_saved'] = true;
            $this->redirect('/tools/' . $toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::update — ' . $e->getMessage());
            $_SESSION['edit_tool_errors'] = ['general' => 'Something went wrong updating your listing. Please try again.'];
            $_SESSION['edit_tool_old'] = $oldInput;
            $this->redirect('/tools/' . $toolId . '/edit');
        }
    }

    /**
     * Toggle a bookmark for the authenticated user on a tool.
     *
     * Delegates to Bookmark::toggle(), flashes a status message, and
     * redirects back to the referring page (preserving query string)
     * or the tool detail page as fallback.
     */
    public function toggleBookmark(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        $tool = ($toolId >= 1) ? Tool::findById($toolId) : null;

        if ($tool === null) {
            $this->abort(404);
        }

        $userId = (int) $_SESSION['user_id'];

        // Owners cannot bookmark their own tools
        if ((int) $tool['owner_id'] === $userId) {
            $_SESSION['bookmark_flash'] = 'You cannot bookmark your own tool.';
            $this->redirect('/tools/' . $toolId);
        }

        try {
            $bookmarked = Bookmark::toggle($userId, $toolId);
            $_SESSION['bookmark_flash'] = $bookmarked
                ? 'Tool bookmarked.'
                : 'Bookmark removed.';
        } catch (\Throwable $e) {
            error_log('ToolController::toggleBookmark — ' . $e->getMessage());
            $_SESSION['bookmark_flash'] = 'Could not update bookmark. Please try again.';
        }

        $back    = '/tools/' . $toolId;
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referer !== '') {
            $parsed  = parse_url($referer);
            $refHost = ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
            $curHost = $_SERVER['HTTP_HOST'] ?? '';

            if ($refHost === $curHost && isset($parsed['path'])) {
                $back = $parsed['path'];

                if (isset($parsed['query'])) {
                    $back .= '?' . $parsed['query'];
                }
            }
        }

        $this->redirect($back);
    }

    /**
     * Soft-delete a tool listing.
     *
     * Validates ownership, then marks the tool as unavailable via
     * Tool::softDelete(). The tool remains in the database for
     * historical records but disappears from search and browse.
     */
    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::delete fetch — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            $this->abort(403);
        }

        try {
            $filenames = Tool::deleteAllImages($toolId);

            foreach ($filenames as $filename) {
                $this->deleteToolImageFiles($filename);
            }

            Tool::softDelete($toolId);
            $_SESSION['lender_notice'] = 'Tool deleted successfully.';
            $this->redirect('/dashboard/lender');
        } catch (\Throwable $e) {
            error_log('ToolController::delete — ' . $e->getMessage());
            $this->abort(500);
        }
    }

    /**
     * Toggle a tool's listing status between listed and unlisted.
     */
    public function toggleListing(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::toggleListing fetch — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            $this->abort(403);
        }

        try {
            Tool::toggleAvailability($toolId);

            $newState = $tool['is_available_tol'] ? 'unlisted' : 're-listed';
            $_SESSION['avb_success'] = 'Tool ' . $newState . ' successfully.';

            $this->redirect('/tools/' . $toolId . '/availability');
        } catch (\Throwable $e) {
            error_log('ToolController::toggleListing — ' . $e->getMessage());
            $this->abort(500);
        }
    }

    /**
     * Handle tool listing form submission.
     *
     * Validates input, processes optional image upload, creates the tool
     * via Tool::create(), and redirects to the new tool's detail page.
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = (int) $_SESSION['user_id'];

        $toolName     = trim($_POST['tool_name'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $categoryId   = (int) ($_POST['category_id'] ?? 0);
        $rentalFee    = $_POST['rental_fee'] ?? '';
        $condition    = trim($_POST['condition'] ?? '');
        $loanDuration = $_POST['loan_duration'] ?? '';
        $usesFuel     = !empty($_POST['uses_fuel']);
        $fuelType     = trim($_POST['fuel_type'] ?? '');

        $errors = $this->validateToolListing($toolName, $categoryId, $rentalFee, $condition, $loanDuration, $usesFuel, $fuelType);

        $uploadedFiles = $this->normalizeFileArray($_FILES['photos'] ?? []);
        $uploadedFiles = array_filter($uploadedFiles, static fn(array $f): bool => $f['error'] !== UPLOAD_ERR_NO_FILE);

        if (count($uploadedFiles) > self::MAX_IMAGES) {
            $errors['photos'] = 'You may upload up to ' . self::MAX_IMAGES . ' photos.';
        }

        foreach ($uploadedFiles as $i => $file) {
            $fileErrors = $this->validateToolImage($file);

            if ($fileErrors !== []) {
                $errors['photos'] = $errors['photos'] ?? reset($fileErrors);
                break;
            }
        }

        $oldInput = [
            'tool_name'     => $toolName,
            'description'   => $description,
            'category_id'   => $categoryId,
            'rental_fee'    => $rentalFee,
            'condition'     => $condition,
            'loan_duration' => $loanDuration,
            'uses_fuel'     => $usesFuel,
            'fuel_type'     => $fuelType,
        ];

        if ($errors !== []) {
            $_SESSION['tool_errors'] = $errors;
            $_SESSION['tool_old'] = $oldInput;
            $this->redirect('/tools/create');
        }

        $focalXValues  = $_POST['focal_x'] ?? [];
        $focalYValues  = $_POST['focal_y'] ?? [];
        $altTextValues = $_POST['alt_text'] ?? [];
        $primaryIndex  = max(0, (int) ($_POST['primary_index'] ?? 0));
        $imageFilenames = [];

        foreach ($uploadedFiles as $i => $file) {
            $result = $this->moveToolImage($file);

            if ($result === null) {
                foreach ($imageFilenames as $saved) {
                    $this->deleteToolImageFiles($saved['filename']);
                }

                $_SESSION['tool_errors'] = ['photos' => 'Failed to save an uploaded image. Please try again.'];
                $_SESSION['tool_old'] = $oldInput;
                $this->redirect('/tools/create');
            }

            $fx = isset($focalXValues[$i]) ? max(0, min(100, (int) $focalXValues[$i])) : 50;
            $fy = isset($focalYValues[$i]) ? max(0, min(100, (int) $focalYValues[$i])) : 50;
            $alt = isset($altTextValues[$i]) && trim($altTextValues[$i]) !== ''
                ? mb_substr(trim($altTextValues[$i]), 0, 255)
                : null;

            $sourcePath = BASE_PATH . '/public/uploads/tools/' . $result['filename'];
            ImageProcessor::generateVariants($sourcePath, focalX: $fx, focalY: $fy);

            $imageFilenames[] = [
                'filename' => $result['filename'],
                'alt_text' => $alt,
                'width'    => $result['width'],
                'focal_x'  => $fx,
                'focal_y'  => $fy,
            ];
        }

        if ($primaryIndex >= count($imageFilenames)) {
            $primaryIndex = 0;
        }

        try {
            $toolId = Tool::create([
                'tool_name'       => $toolName,
                'description'     => $description !== '' ? $description : null,
                'rental_fee'      => (float) $rentalFee,
                'owner_id'        => $userId,
                'category_id'     => $categoryId,
                'condition'       => $condition,
                'loan_duration'   => $loanDuration !== '' ? (int) $loanDuration * 24 : null,
                'fuel_type'       => $usesFuel && $fuelType !== '' ? $fuelType : null,
                'image_filenames' => $imageFilenames,
                'primary_index'   => $primaryIndex,
            ]);

            $_SESSION['tool_saved'] = true;
            $this->redirect('/tools/' . $toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::store — ' . $e->getMessage());

            foreach ($imageFilenames as $saved) {
                $this->deleteToolImageFiles($saved['filename']);
            }

            $_SESSION['tool_errors'] = ['general' => 'Something went wrong creating your listing. Please try again.'];
            $_SESSION['tool_old'] = $oldInput;
            $this->redirect('/tools/create');
        }
    }

    /**
     * Normalize a multi-file $_FILES array into an array of per-file arrays.
     *
     * PHP stores multi-uploads as ['name' => [...], 'tmp_name' => [...], ...]
     * This converts to [['name' => ..., 'tmp_name' => ..., ...], ...].
     *
     * @param  array $files  The $_FILES entry for a multi-file input
     * @return array<int, array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private function normalizeFileArray(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $normalized = [];

        foreach ($files['name'] as $i => $name) {
            $normalized[] = [
                'name'     => $name,
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }

        return $normalized;
    }

    /**
     * Validate tool listing form fields.
     *
     * @return array<string, string>  Field-keyed error messages (empty = valid)
     */
    private const array VALID_FUEL_TYPES = [
        'gasoline', 'diesel', 'propane', 'two-stroke mix',
        'electric/battery', 'kerosene', 'natural gas',
    ];

    private function validateToolListing(
        string $name,
        int $categoryId,
        string $fee,
        string $condition,
        string $loanDuration,
        bool $usesFuel = false,
        string $fuelType = '',
    ): array {
        $errors = [];

        if ($name === '') {
            $errors['tool_name'] = 'Tool name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['tool_name'] = 'Tool name must be 100 characters or fewer.';
        }

        if ($categoryId < 1) {
            $errors['category_id'] = 'Please select a category.';
        }

        if ($fee === '' || !is_numeric($fee)) {
            $errors['rental_fee'] = 'Rental fee is required and must be a number.';
        } elseif ((float) $fee < 0 || (float) $fee > 9999) {
            $errors['rental_fee'] = 'Rental fee must be between $0 and $9,999.';
        }

        $validConditions = ['new', 'good', 'fair', 'poor'];
        if (!in_array($condition, $validConditions, true)) {
            $errors['condition'] = 'Please select a valid condition.';
        }

        if ($loanDuration !== '' && (!ctype_digit($loanDuration) || (int) $loanDuration < 1 || (int) $loanDuration > 30)) {
            $errors['loan_duration'] = 'Loan duration must be between 1 and 30 days.';
        }

        if ($usesFuel) {
            if ($fuelType === '') {
                $errors['fuel_type'] = 'Please select a fuel type.';
            } elseif (!in_array($fuelType, self::VALID_FUEL_TYPES, true)) {
                $errors['fuel_type'] = 'Please select a valid fuel type.';
            }
        }

        return $errors;
    }

    /**
     * Validate an uploaded tool image file.
     *
     * Checks upload status, file size (max 5 MB), and MIME type
     * via finfo (not the untrusted $_FILES type).
     *
     * @param  array  $file  The $_FILES['tool_image'] entry
     * @return array<string, string>  Error messages (empty = valid)
     */
    private function validateToolImage(array $file): array
    {
        $errors = [];
        $maxSize = 5 * 1024 * 1024; // 5 MB
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['tool_image'] = 'Image upload failed. Please try again.';
            return $errors;
        }

        if ($file['size'] > $maxSize) {
            $errors['tool_image'] = 'Image must be 5 MB or smaller.';
            return $errors;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowedMimes, true)) {
            $errors['tool_image'] = 'Image must be a JPEG, PNG, or WebP file.';
        }

        return $errors;
    }

    /**
     * Move a validated tool image to the uploads directory.
     *
     * @return array{filename: string, width: ?int}|null
     */
    private function moveToolImage(array $file): ?array
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = $extensions[$mime] ?? 'jpg';

        $filename = uniqid('tool_', true) . '.' . $ext;
        $destination = BASE_PATH . '/public/uploads/tools/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        ImageProcessor::autoOrient($destination);

        ImageProcessor::resize($destination, 1600);

        $width = ImageProcessor::getIntrinsicWidth($destination);

        return ['filename' => $filename, 'width' => $width];
    }

    /**
     * Delete a tool image and all size/format variants from disk.
     */
    private function deleteToolImageFiles(string $filename): void
    {
        ImageProcessor::deleteVariants($filename);
    }

    /**
     * Show availability blocks for a tool (owner-only).
     */
    public function availability(string $id): void
    {
        $this->requireAuth();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::availability — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            $this->abort(403);
        }

        $blocks = [];

        try {
            $blocks = AvailabilityBlock::getForTool($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::availability blocks — ' . $e->getMessage());
        }

        $this->render('tools/availability', [
            'title'   => 'Manage Availability — ' . $tool['tool_name_tol'],
            'pageCss' => ['tools.css'],
            'tool'    => $tool,
            'blocks'  => $blocks,
            'errors'  => $this->flash('avb_errors', []),
            'old'     => $this->flash('avb_old', []),
            'success' => $this->flash('avb_success', ''),
        ]);
    }

    /**
     * Add an admin availability block to a tool.
     */
    public function addBlock(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::addBlock fetch — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            $this->abort(403);
        }

        $startAt = trim($_POST['start_at'] ?? '');
        $endAt   = trim($_POST['end_at'] ?? '');
        $notes   = trim($_POST['notes'] ?? '');

        $errors = [];

        if ($startAt === '') {
            $errors['start_at'] = 'Start date is required.';
        } elseif (strtotime($startAt) === false) {
            $errors['start_at'] = 'Start date is not a valid date.';
        }

        if ($endAt === '') {
            $errors['end_at'] = 'End date is required.';
        } elseif (strtotime($endAt) === false) {
            $errors['end_at'] = 'End date is not a valid date.';
        }

        if ($errors === [] && strtotime($endAt) <= strtotime($startAt)) {
            $errors['end_at'] = 'End date must be after the start date.';
        }

        if (!isset($errors['start_at']) && $startAt !== '' && strtotime($startAt) < strtotime('today')) {
            $errors['start_at'] = 'Start date cannot be in the past.';
        }

        if (mb_strlen($notes) > 500) {
            $errors['notes'] = 'Notes must be 500 characters or fewer.';
        }

        $oldInput = [
            'start_at' => $startAt,
            'end_at'   => $endAt,
            'notes'    => $notes,
        ];

        if ($errors !== []) {
            $_SESSION['avb_errors'] = $errors;
            $_SESSION['avb_old'] = $oldInput;
            $this->redirect('/tools/' . $toolId . '/availability');
        }

        try {
            AvailabilityBlock::create(
                toolId: $toolId,
                startAt: date('Y-m-d 00:00:00', strtotime($startAt)),
                endAt: date('Y-m-d 23:59:59', strtotime($endAt)),
                notes: $notes !== '' ? $notes : null,
            );

            $_SESSION['avb_success'] = 'Availability block added.';
        } catch (\Throwable $e) {
            error_log('ToolController::addBlock — ' . $e->getMessage());
            $_SESSION['avb_errors'] = ['general' => 'Something went wrong adding the block. Please try again.'];
            $_SESSION['avb_old'] = $oldInput;
        }

        $this->redirect('/tools/' . $toolId . '/availability');
    }

    /**
     * Remove an admin availability block from a tool.
     */
    public function removeBlock(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        $tool = null;

        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::removeBlock fetch — ' . $e->getMessage());
        }

        if ($tool === null) {
            $this->abort(404);
        }

        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            $this->abort(403);
        }

        $blockId = (int) ($_POST['block_id'] ?? 0);

        if ($blockId < 1) {
            $this->abort(404);
        }

        $block = null;

        try {
            $block = AvailabilityBlock::findById($blockId);
        } catch (\Throwable $e) {
            error_log('ToolController::removeBlock block — ' . $e->getMessage());
        }

        if ($block === null) {
            $this->abort(404);
        }

        if ((int) $block['id_tol_avb'] !== $toolId) {
            $this->abort(403);
        }

        if ($block['block_type'] !== 'admin') {
            $_SESSION['avb_errors'] = ['general' => 'System-managed borrow blocks cannot be removed.'];
            $this->redirect('/tools/' . $toolId . '/availability');
        }

        try {
            AvailabilityBlock::delete($blockId);
            $_SESSION['avb_success'] = 'Availability block removed.';
        } catch (\Throwable $e) {
            error_log('ToolController::removeBlock — ' . $e->getMessage());
            $_SESSION['avb_errors'] = ['general' => 'Something went wrong removing the block. Please try again.'];
        }

        $this->redirect('/tools/' . $toolId . '/availability');
    }

    private const int MAX_IMAGES = 6;

    /**
     * Upload a new image for a tool (AJAX).
     */
    public function uploadImage(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;
        $json   = $this->wantsJson();

        if ($toolId < 1) {
            $json ? $this->jsonResponse(404, ['error' => 'Tool not found']) : $this->abort(404);
        }

        $tool = $this->findOwnedTool($toolId);

        if ($tool === null) {
            $json ? $this->jsonResponse(403, ['error' => 'Unauthorized']) : $this->abort(403);
        }

        try {
            $count = Tool::getImageCount($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::uploadImage count — ' . $e->getMessage());
            if ($json) {
                $this->jsonResponse(500, ['error' => 'Server error']);
            }
            $this->abort(500);
        }

        if ($count >= self::MAX_IMAGES) {
            $errorMsg = 'Maximum of ' . self::MAX_IMAGES . ' images allowed';
            if ($json) {
                $this->jsonResponse(422, ['error' => $errorMsg]);
            }
            $_SESSION['edit_tool_errors'] = ['photos' => $errorMsg];
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        $hasFile = isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;

        if (!$hasFile) {
            if ($json) {
                $this->jsonResponse(422, ['error' => 'No image file provided']);
            }
            $_SESSION['edit_tool_errors'] = ['photos' => 'No image file provided'];
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        $errors = $this->validateToolImage($_FILES['photo']);

        if ($errors !== []) {
            $errorMsg = reset($errors);
            if ($json) {
                $this->jsonResponse(422, ['error' => $errorMsg]);
            }
            $_SESSION['edit_tool_errors'] = ['photos' => $errorMsg];
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        $result = $this->moveToolImage($_FILES['photo']);

        if ($result === null) {
            if ($json) {
                $this->jsonResponse(500, ['error' => 'Failed to save image']);
            }
            $_SESSION['edit_tool_errors'] = ['photos' => 'Failed to save image'];
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        $filename = $result['filename'];
        $width    = $result['width'];

        $altText   = isset($_POST['alt_text']) ? mb_substr(trim($_POST['alt_text']), 0, 255) : null;
        $altText   = $altText !== '' ? $altText : null;
        $isPrimary = $count === 0;
        $sortOrder = $count + 1;

        $focalX = isset($_POST['focal_x']) ? max(0, min(100, (int) $_POST['focal_x'])) : 50;
        $focalY = isset($_POST['focal_y']) ? max(0, min(100, (int) $_POST['focal_y'])) : 50;

        $sourcePath = BASE_PATH . '/public/uploads/tools/' . $filename;
        $created = ImageProcessor::generateVariants($sourcePath, focalX: $focalX, focalY: $focalY);

        if ($created === [] && $width !== null && $width > 540) {
            $this->deleteToolImageFiles($filename);
            $errorMsg = 'Failed to generate image variants';
            if ($json) {
                $this->jsonResponse(500, ['error' => $errorMsg]);
            }
            $_SESSION['edit_tool_errors'] = ['photos' => $errorMsg];
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        try {
            $imageId = Tool::addImage($toolId, $filename, $altText, $isPrimary, $sortOrder, $focalX, $focalY, $width);

            if ($json) {
                $variants = ImageProcessor::getAvailableVariants($filename, $width);
                $thumbKey = array_key_first($variants);
                $thumb    = $thumbKey !== null ? $variants[$thumbKey]['file'] : $filename;

                $this->jsonResponse(200, [
                    'id'         => $imageId,
                    'filename'   => $filename,
                    'thumb'      => $thumb,
                    'alt_text'   => $altText,
                    'sort_order' => $sortOrder,
                    'is_primary' => $isPrimary,
                    'focal_x'    => $focalX,
                    'focal_y'    => $focalY,
                    'width'      => $width,
                ]);
            }

            $_SESSION['tool_saved'] = true;
            $this->redirect('/tools/' . $toolId . '/edit');
        } catch (\Throwable $e) {
            error_log('ToolController::uploadImage — ' . $e->getMessage());
            $this->deleteToolImageFiles($filename);

            if ($json) {
                $this->jsonResponse(500, ['error' => 'Failed to save image record']);
            }

            $_SESSION['edit_tool_errors'] = ['photos' => 'Failed to save image record'];
            $this->redirect('/tools/' . $toolId . '/edit');
        }
    }

    /**
     * Delete a tool image (AJAX or form POST with _method=DELETE).
     */
    public function deleteImage(string $id, string $img): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId  = (int) $id;
        $imageId = (int) $img;

        if ($toolId < 1 || $imageId < 1) {
            $this->jsonResponse(404, ['error' => 'Not found']);
        }

        $tool = $this->findOwnedTool($toolId);

        if ($tool === null) {
            $this->jsonResponse(403, ['error' => 'Unauthorized']);
        }

        if (!Tool::imageBelongsTo($imageId, $toolId)) {
            $this->jsonResponse(404, ['error' => 'Image not found']);
        }

        try {
            $filename = Tool::deleteImage($imageId);

            if ($filename !== null) {
                $this->deleteToolImageFiles($filename);
            }

            $newPrimary = null;
            $images = Tool::getImages($toolId);

            foreach ($images as $image) {
                if ($image['is_primary_tim']) {
                    $newPrimary = (int) $image['id_tim'];
                    break;
                }
            }

            $this->jsonResponse(200, [
                'deleted'        => true,
                'new_primary_id' => $newPrimary,
            ]);
        } catch (\Throwable $e) {
            error_log('ToolController::deleteImage — ' . $e->getMessage());
            $this->jsonResponse(500, ['error' => 'Failed to delete image']);
        }
    }

    /**
     * Reorder tool images via drag-drop (AJAX, JSON body).
     */
    public function reorderImages(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->jsonResponse(404, ['error' => 'Tool not found']);
        }

        $tool = $this->findOwnedTool($toolId);

        if ($tool === null) {
            $this->jsonResponse(403, ['error' => 'Unauthorized']);
        }

        $body = $this->getJsonBody();
        $order = $body['order'] ?? [];

        if (!is_array($order) || $order === []) {
            $this->jsonResponse(422, ['error' => 'Order array is required']);
        }

        $existingImages = Tool::getImages($toolId);
        $existingIds = array_map(static fn(array $img): int => (int) $img['id_tim'], $existingImages);

        $orderedIds = array_map('intval', $order);

        if (count($orderedIds) !== count($existingIds)
            || array_diff($orderedIds, $existingIds) !== []
        ) {
            $this->jsonResponse(422, ['error' => 'Order must contain all image IDs for this tool']);
        }

        try {
            Tool::reorderImages($toolId, $orderedIds);
            $this->jsonResponse(200, ['success' => true]);
        } catch (\Throwable $e) {
            error_log('ToolController::reorderImages — ' . $e->getMessage());
            $this->jsonResponse(500, ['error' => 'Failed to reorder images']);
        }
    }

    /**
     * Set a tool image as the primary (AJAX).
     */
    public function setPrimary(string $id, string $img): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId  = (int) $id;
        $imageId = (int) $img;

        if ($toolId < 1 || $imageId < 1) {
            $this->jsonResponse(404, ['error' => 'Not found']);
        }

        $tool = $this->findOwnedTool($toolId);

        if ($tool === null) {
            $this->jsonResponse(403, ['error' => 'Unauthorized']);
        }

        if (!Tool::imageBelongsTo($imageId, $toolId)) {
            $this->jsonResponse(404, ['error' => 'Image not found']);
        }

        try {
            Tool::setPrimaryImage($toolId, $imageId);
            $this->jsonResponse(200, ['success' => true]);
        } catch (\Throwable $e) {
            error_log('ToolController::setPrimary — ' . $e->getMessage());
            $this->jsonResponse(500, ['error' => 'Failed to set primary image']);
        }
    }

    /**
     * Update alt text for a tool image (AJAX, JSON body).
     */
    public function updateImage(string $id, string $img): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId  = (int) $id;
        $imageId = (int) $img;

        if ($toolId < 1 || $imageId < 1) {
            $this->jsonResponse(404, ['error' => 'Not found']);
        }

        $tool = $this->findOwnedTool($toolId);

        if ($tool === null) {
            $this->jsonResponse(403, ['error' => 'Unauthorized']);
        }

        if (!Tool::imageBelongsTo($imageId, $toolId)) {
            $this->jsonResponse(404, ['error' => 'Image not found']);
        }

        $body = $this->getJsonBody();

        try {
            if (isset($body['alt_text'])) {
                $altText = mb_substr(trim($body['alt_text']), 0, 255);
                Tool::updateImageAltText($imageId, $altText);
            }

            if (isset($body['focal_x'], $body['focal_y'])) {
                $focalX = max(0, min(100, (int) $body['focal_x']));
                $focalY = max(0, min(100, (int) $body['focal_y']));
                Tool::updateFocalPoint($imageId, $focalX, $focalY);

                $image = Tool::getImageById($imageId);
                if ($image !== null) {
                    $sourcePath = BASE_PATH . '/public/uploads/tools/' . $image['file_name_tim'];
                    ImageProcessor::deleteVariantsOnly($image['file_name_tim']);
                    ImageProcessor::generateVariants($sourcePath, focalX: $focalX, focalY: $focalY);
                }
            }

            $this->jsonResponse(200, ['success' => true]);
        } catch (\Throwable $e) {
            error_log('ToolController::updateImage — ' . $e->getMessage());
            $this->jsonResponse(500, ['error' => 'Failed to update image']);
        }
    }

    /**
     * Fetch a tool and verify ownership by the current user.
     *
     * @return ?array Tool data, or null if not found / not owned
     */
    private function findOwnedTool(int $toolId): ?array
    {
        try {
            $tool = Tool::findById($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::findOwnedTool — ' . $e->getMessage());
            return null;
        }

        if ($tool === null) {
            return null;
        }

        if ((int) $tool['owner_id'] !== (int) $_SESSION['user_id']) {
            return null;
        }

        return $tool;
    }

}
