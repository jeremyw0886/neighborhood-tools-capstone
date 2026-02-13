<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;

/**
 * Handles static informational pages.
 *
 * These serve as progressive-enhancement fallbacks for the <dialog> modals:
 * without JavaScript, nav links like /how-to and /faq navigate here instead.
 * With JS, the same content is shown inside a modal — both share content
 * partials (content-how-to.php, content-faq.php) to avoid duplication.
 */
class PageController extends BaseController
{
    /**
     * Render the standalone How It Works page.
     */
    public function howTo(): void
    {
        $this->render('pages/how-to', [
            'title'       => 'How It Works — NeighborhoodTools',
            'description' => 'Learn how to borrow and lend tools with NeighborhoodTools — for borrowers, lenders, and community safety.',
            'pageCss'     => ['pages.css'],
        ]);
    }

    /**
     * Render the standalone FAQs page.
     */
    public function faq(): void
    {
        $this->render('pages/faq', [
            'title'       => 'FAQs — NeighborhoodTools',
            'description' => 'Frequently asked questions about using NeighborhoodTools — accounts, deposits, disputes, ratings, and more.',
            'pageCss'     => ['pages.css'],
        ]);
    }
}
