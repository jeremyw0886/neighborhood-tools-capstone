<?php

declare(strict_types=1);

/**
 * Ordered CSS manifest — base first, responsive last.
 *
 * This is the single source of truth for CSS file loading order.
 * Both the layout (dev mode) and build-css.php (production bundle)
 * read from this file.
 *
 * After adding or removing files, run: php build-css.php
 */

return [
    'base.css',
    'nav.css',
    'modal.css',
    'auth.css',
    'pages.css',
    'errors.css',
    'home.css',
    'responsive.css',
];
