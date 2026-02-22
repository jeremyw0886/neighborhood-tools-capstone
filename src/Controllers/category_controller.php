<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Category;

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
        try {
            $categories = Category::getAll();
        } catch (\Throwable $e) {
            error_log('CategoryController::index failed: ' . $e->getMessage());
            $categories = [];
        }

        $this->render('categories/index', [
            'title'       => 'Categories — NeighborhoodTools',
            'description' => 'Browse tools by category — power tools, hand tools, garden equipment, and more from your neighbors.',
            'pageCss'     => ['categories.css'],
            'categories'  => $categories,
        ]);
    }
}
