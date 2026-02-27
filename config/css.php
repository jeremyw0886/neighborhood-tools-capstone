<?php

declare(strict_types=1);

/**
 * Ordered CSS manifest — base first, responsive last.
 *
 * This is the single source of truth for CSS file loading order.
 * Both the layout (dev mode) and build-assets.php (production bundle)
 * read from this file.
 *
 * After adding or removing files, run: php build-assets.php
 */

return [
    'base.css',
    'components.css',
    'responsive.css',
];
