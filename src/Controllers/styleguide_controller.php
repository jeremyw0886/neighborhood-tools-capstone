<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;

/**
 * Renders the internal design-system reference page.
 */
class StyleguideController extends BaseController
{
    /**
     * Render the style guide.
     */
    public function index(): void
    {
        $this->render('styleguide/index', [
            'title'       => 'Style Guide — NeighborhoodTools',
            'description' => 'Design system reference: tokens, typography, buttons, forms, badges, and components used across NeighborhoodTools.',
            'pageCss'     => ['styleguide.css'],
            'pageJs'      => ['styleguide.js'],
        ]);
    }
}
