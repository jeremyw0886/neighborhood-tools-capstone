<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Bookmark;
use App\Models\Tool;

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
        // Read and sanitize filter params from query string
        $term       = trim($_GET['q'] ?? '');
        $categoryId = ($_GET['category'] ?? '') !== '' ? (int) $_GET['category'] : null;
        $zip        = trim($_GET['zip'] ?? '') !== '' ? trim($_GET['zip']) : null;
        $maxFee     = ($_GET['max_fee'] ?? '') !== '' ? (float) $_GET['max_fee'] : null;
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $offset     = ($page - 1) * self::PER_PAGE;

        try {
            $tools = Tool::search(
                term: $term,
                categoryId: $categoryId,
                zip: $zip,
                maxFee: $maxFee,
                limit: self::PER_PAGE,
                offset: $offset,
            );

            $totalCount = Tool::searchCount(
                term: $term,
                categoryId: $categoryId,
                zip: $zip,
                maxFee: $maxFee,
            );

            $categories = Tool::getCategories();

            // Enrich search results with category names for card display
            if ($tools !== []) {
                $toolIds     = array_column($tools, 'id_tol');
                $categoryMap = Tool::getCategoryNamesForTools($toolIds);

                foreach ($tools as &$t) {
                    $t['category_name'] = $categoryMap[(int) $t['id_tol']] ?? null;
                }
                unset($t);
            }
        } catch (\Throwable $e) {
            error_log('ToolController::index — ' . $e->getMessage());
            $tools      = [];
            $totalCount = 0;
            $categories = [];
        }

        $totalPages = (int) ceil($totalCount / self::PER_PAGE) ?: 1;

        // Clamp page to valid range
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        // Build query string for pagination links (preserves all active filters)
        $filterParams = array_filter([
            'q'        => $term !== '' ? $term : null,
            'category' => $categoryId,
            'zip'      => $zip,
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

        $this->render('tools/index', [
            'title'         => 'Browse Tools — NeighborhoodTools',
            'description'   => 'Search and browse available tools to borrow from your neighbors in the Asheville and Hendersonville areas.',
            'pageCss'       => ['tools.css'],
            'tools'         => $tools,
            'categories'    => $categories,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
            'filterParams'  => $filterParams,
            'term'          => $term,
            'categoryId'    => $categoryId,
            'zip'           => $zip,
            'maxFee'        => $maxFee,
            'sliderMax'     => $sliderMax,
            'sliderValue'   => $sliderValue,
            'bookmarkedIds' => $bookmarkedIds,
        ]);
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

        $this->render('tools/bookmarks', [
            'title'         => 'My Bookmarks — NeighborhoodTools',
            'description'   => 'Your saved tools — NeighborhoodTools',
            'pageCss'       => ['tools.css'],
            'bookmarks'     => $bookmarks,
            'totalCount'    => $totalCount,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'perPage'       => self::PER_PAGE,
            'bookmarkedIds' => $bookmarkedIds,
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

        $this->render('tools/show', [
            'title'        => htmlspecialchars($tool['tool_name_tol']) . ' — NeighborhoodTools',
            'pageCss'      => ['tools.css'],
            'tool'         => $tool,
            'isBookmarked' => $isBookmarked,
            'isOwner'      => $isOwner,
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

        try {
            $categories = Tool::getCategories();
        } catch (\Throwable $e) {
            error_log('ToolController::create — ' . $e->getMessage());
        }

        // Recover flash data from failed submission
        $errors = $_SESSION['tool_errors'] ?? [];
        $old    = $_SESSION['tool_old'] ?? [];
        unset($_SESSION['tool_errors'], $_SESSION['tool_old']);

        $this->render('tools/create', [
            'title'      => 'List a Tool — NeighborhoodTools',
            'pageCss'    => ['tools.css'],
            'categories' => $categories,
            'errors'     => $errors,
            'old'        => $old,
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

        try {
            $categories = Tool::getCategories();
        } catch (\Throwable $e) {
            error_log('ToolController::edit categories — ' . $e->getMessage());
        }

        // Get current category ID for pre-selecting the dropdown
        $currentCategoryId = null;

        try {
            $currentCategoryId = Tool::getCategoryId($toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::edit categoryId — ' . $e->getMessage());
        }

        // Flash data from a failed update() overrides DB values
        $errors = $_SESSION['edit_tool_errors'] ?? [];
        $old    = $_SESSION['edit_tool_old'] ?? [];
        unset($_SESSION['edit_tool_errors'], $_SESSION['edit_tool_old']);

        $this->render('tools/edit', [
            'title'             => 'Edit ' . htmlspecialchars($tool['tool_name_tol']) . ' — NeighborhoodTools',
            'pageCss'           => ['tools.css'],
            'tool'              => $tool,
            'categories'        => $categories,
            'currentCategoryId' => $currentCategoryId,
            'errors'            => $errors,
            'old'               => $old,
        ]);
    }

    /**
     * Handle tool edit form submission.
     *
     * Validates ownership, input, and optional image upload, then persists
     * changes via Tool::update(). On success, cleans up the old image file
     * if a replacement was uploaded and redirects to the tool detail page.
     */
    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $toolId = (int) $id;

        if ($toolId < 1) {
            $this->abort(404);
        }

        // Fetch tool for ownership check (also captures old image filename)
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

        // Extract and sanitize POST data
        $toolName     = trim($_POST['tool_name'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $categoryId   = (int) ($_POST['category_id'] ?? 0);
        $rentalFee    = $_POST['rental_fee'] ?? '';
        $condition    = trim($_POST['condition'] ?? '');
        $loanDuration = $_POST['loan_duration'] ?? '';

        // Validate fields
        $errors = $this->validateToolListing($toolName, $categoryId, $rentalFee, $condition, $loanDuration);

        // Validate image (if one was uploaded)
        $imageFilename = null;
        $hasImage = isset($_FILES['tool_image'])
            && $_FILES['tool_image']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasImage) {
            $imageErrors = $this->validateToolImage($_FILES['tool_image']);
            $errors = array_merge($errors, $imageErrors);
        }

        // Build sticky flash data array
        $oldInput = [
            'tool_name'     => $toolName,
            'description'   => $description,
            'category_id'   => $categoryId,
            'rental_fee'    => $rentalFee,
            'condition'     => $condition,
            'loan_duration' => $loanDuration,
        ];

        // On validation failure, flash errors + old input and redirect back
        if ($errors !== []) {
            $_SESSION['edit_tool_errors'] = $errors;
            $_SESSION['edit_tool_old'] = $oldInput;
            $this->redirect('/tools/' . $toolId . '/edit');
        }

        // Move uploaded file to disk (after validation passed)
        if ($hasImage) {
            $imageFilename = $this->moveToolImage($_FILES['tool_image']);

            if ($imageFilename === null) {
                $_SESSION['edit_tool_errors'] = ['tool_image' => 'Failed to save the uploaded image. Please try again.'];
                $_SESSION['edit_tool_old'] = $oldInput;
                $this->redirect('/tools/' . $toolId . '/edit');
            }
        }

        // Persist changes via model
        try {
            Tool::update($toolId, [
                'tool_name'      => $toolName,
                'description'    => $description !== '' ? $description : null,
                'rental_fee'     => (float) $rentalFee,
                'condition'      => $condition,
                'loan_duration'  => $loanDuration !== '' ? (int) $loanDuration : null,
                'category_id'    => $categoryId,
                'image_filename' => $imageFilename,
            ]);

            // Delete old image file after successful DB commit
            if ($imageFilename !== null && !empty($tool['primary_image'])) {
                $oldPath = BASE_PATH . '/public/uploads/tools/' . $tool['primary_image'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $_SESSION['tool_saved'] = true;
            $this->redirect('/tools/' . $toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::update — ' . $e->getMessage());

            // Clean up orphaned new image on DB failure
            if ($imageFilename !== null) {
                $path = BASE_PATH . '/public/uploads/tools/' . $imageFilename;
                if (file_exists($path)) {
                    unlink($path);
                }
            }

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
            return;
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

        // Redirect to referer path (same-origin safe) or tool detail
        $back = '/tools/' . $toolId;

        if (isset($_SERVER['HTTP_REFERER'])) {
            $parsed = parse_url($_SERVER['HTTP_REFERER']);

            if (isset($parsed['path'])) {
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
            Tool::softDelete($toolId);
            $this->redirect('/tools');
        } catch (\Throwable $e) {
            error_log('ToolController::delete — ' . $e->getMessage());
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

        // Extract and sanitize POST data
        $toolName     = trim($_POST['tool_name'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $categoryId   = (int) ($_POST['category_id'] ?? 0);
        $rentalFee    = $_POST['rental_fee'] ?? '';
        $condition    = trim($_POST['condition'] ?? '');
        $loanDuration = $_POST['loan_duration'] ?? '';

        // Validate fields
        $errors = $this->validateToolListing($toolName, $categoryId, $rentalFee, $condition, $loanDuration);

        // Validate image (if one was uploaded)
        $imageFilename = null;
        $hasImage = isset($_FILES['tool_image'])
            && $_FILES['tool_image']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasImage) {
            $imageErrors = $this->validateToolImage($_FILES['tool_image']);
            $errors = array_merge($errors, $imageErrors);
        }

        // Build sticky flash data array
        $oldInput = [
            'tool_name'     => $toolName,
            'description'   => $description,
            'category_id'   => $categoryId,
            'rental_fee'    => $rentalFee,
            'condition'     => $condition,
            'loan_duration' => $loanDuration,
        ];

        // On validation failure, flash errors + old input and redirect back
        if ($errors !== []) {
            $_SESSION['tool_errors'] = $errors;
            $_SESSION['tool_old'] = $oldInput;
            $this->redirect('/tools/create');
        }

        // Move uploaded file to disk (after validation passed)
        if ($hasImage) {
            $imageFilename = $this->moveToolImage($_FILES['tool_image']);

            if ($imageFilename === null) {
                $_SESSION['tool_errors'] = ['tool_image' => 'Failed to save the uploaded image. Please try again.'];
                $_SESSION['tool_old'] = $oldInput;
                $this->redirect('/tools/create');
            }
        }

        // Create tool via model
        try {
            $toolId = Tool::create([
                'tool_name'      => $toolName,
                'description'    => $description !== '' ? $description : null,
                'rental_fee'     => (float) $rentalFee,
                'owner_id'       => $userId,
                'category_id'    => $categoryId,
                'condition'      => $condition,
                'loan_duration'  => $loanDuration !== '' ? (int) $loanDuration : null,
                'image_filename' => $imageFilename,
            ]);

            $_SESSION['tool_saved'] = true;
            $this->redirect('/tools/' . $toolId);
        } catch (\Throwable $e) {
            error_log('ToolController::store — ' . $e->getMessage());

            // Clean up orphaned image file on DB failure
            if ($imageFilename !== null) {
                $path = BASE_PATH . '/public/uploads/tools/' . $imageFilename;
                if (file_exists($path)) {
                    unlink($path);
                }
            }

            $_SESSION['tool_errors'] = ['general' => 'Something went wrong creating your listing. Please try again.'];
            $_SESSION['tool_old'] = $oldInput;
            $this->redirect('/tools/create');
        }
    }

    /**
     * Validate tool listing form fields.
     *
     * @return array<string, string>  Field-keyed error messages (empty = valid)
     */
    private function validateToolListing(
        string $name,
        int $categoryId,
        string $fee,
        string $condition,
        string $loanDuration,
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

        if ($loanDuration !== '' && (!ctype_digit($loanDuration) || (int) $loanDuration < 1 || (int) $loanDuration > 720)) {
            $errors['loan_duration'] = 'Loan duration must be between 1 and 720 hours.';
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
     * Generates a unique filename to prevent collisions. Returns the
     * filename on success, null on failure.
     */
    private function moveToolImage(array $file): ?string
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

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }

        return null;
    }
}
