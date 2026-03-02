<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Category;
use App\Models\Tool;

class CategoryController extends BaseController
{
    /**
     * Browse tool categories with stats.
     *
     * Displays all categories in a card grid. Each card shows the
     * category's tool counts, average rating, and rental fee range,
     * linking through to /tools?category={id} for filtered browsing.
     */
    public function index(): void
    {
        $excludeOwnerId = !empty($_SESSION['logged_in']) ? (int) $_SESSION['user_id'] : null;

        try {
            $categories   = Category::getAll();
            $browseCounts = Tool::getBrowseableCountsByCategory($excludeOwnerId);
        } catch (\Throwable $e) {
            error_log('CategoryController::index failed: ' . $e->getMessage());
            $categories   = [];
            $browseCounts = [];
        }

        $this->render('categories/index', [
            'title'        => 'Categories — NeighborhoodTools',
            'description'  => 'Browse tools by category — power tools, hand tools, garden equipment, and more from your neighbors.',
            'pageCss'      => ['tools.css'],
            'categories'   => $categories,
            'browseCounts' => $browseCounts,
        ]);
    }
}
