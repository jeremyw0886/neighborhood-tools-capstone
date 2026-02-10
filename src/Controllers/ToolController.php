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

        $this->render('tools/create', [
            'title'      => 'List a Tool — NeighborhoodTools',
            'pageCss'    => ['tools.css'],
            'categories' => $categories,
        ]);
    }
}
