<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
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

        $this->render('tools/index', [
            'title'        => 'Browse Tools — NeighborhoodTools',
            'description'  => 'Search and browse available tools to borrow from your neighbors in the Asheville and Hendersonville areas.',
            'pageCss'      => ['tools.css'],
            'tools'        => $tools,
            'categories'   => $categories,
            'totalCount'   => $totalCount,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => self::PER_PAGE,
            'filterParams' => $filterParams,
            'term'         => $term,
            'categoryId'   => $categoryId,
            'zip'          => $zip,
            'maxFee'       => $maxFee,
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

        $this->render('tools/show', [
            'title'   => htmlspecialchars($tool['tool_name_tol']) . ' — NeighborhoodTools',
            'pageCss' => ['tools.css'],
            'tool'    => $tool,
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
        $toolName   = trim($_POST['tool_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $rentalFee  = $_POST['rental_fee'] ?? '';

        // Validate fields
        $errors = $this->validateToolListing($toolName, $categoryId, $rentalFee);

        // Validate image (if one was uploaded)
        $imageFilename = null;
        $hasImage = isset($_FILES['tool_image'])
            && $_FILES['tool_image']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasImage) {
            $imageErrors = $this->validateToolImage($_FILES['tool_image']);
            $errors = array_merge($errors, $imageErrors);
        }

        // On validation failure, flash errors + old input and redirect back
        if ($errors !== []) {
            $_SESSION['tool_errors'] = $errors;
            $_SESSION['tool_old'] = [
                'tool_name'   => $toolName,
                'description' => $description,
                'category_id' => $categoryId,
                'rental_fee'  => $rentalFee,
            ];
            $this->redirect('/tools/create');
        }

        // Move uploaded file to disk (after validation passed)
        if ($hasImage) {
            $imageFilename = $this->moveToolImage($_FILES['tool_image']);

            if ($imageFilename === null) {
                $_SESSION['tool_errors'] = ['tool_image' => 'Failed to save the uploaded image. Please try again.'];
                $_SESSION['tool_old'] = [
                    'tool_name'   => $toolName,
                    'description' => $description,
                    'category_id' => $categoryId,
                    'rental_fee'  => $rentalFee,
                ];
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
                'image_filename' => $imageFilename,
            ]);

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
            $_SESSION['tool_old'] = [
                'tool_name'   => $toolName,
                'description' => $description,
                'category_id' => $categoryId,
                'rental_fee'  => $rentalFee,
            ];
            $this->redirect('/tools/create');
        }
    }

    /**
     * Validate tool listing form fields.
     *
     * @return array<string, string>  Field-keyed error messages (empty = valid)
     */
    private function validateToolListing(string $name, int $categoryId, string $fee): array
    {
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
