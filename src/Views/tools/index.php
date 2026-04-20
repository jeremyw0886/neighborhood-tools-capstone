<?php
/**
 * Browse Tools — search, filter, and paginate tools.
 *
 * Shared by ToolController::index() (All Tools) and
 * AvailableController::index() (Available Tools). The $availableOnly
 * flag switches the heading, active tab, and form target.
 *
 * @var array   $tools        Tool rows
 * @var array   $categories   Rows from category_summary_fast_v
 * @var array   $browseCounts Per-category tool counts [category_id => count]
 * @var int     $totalCount   Total matching tools (for pagination)
 * @var int     $page         Current page number (1-based)
 * @var int     $totalPages   Total pages
 * @var int     $perPage      Results per page (12)
 * @var array   $filterParams Active filters — nulls stripped
 * @var string  $term         Current search term (may be '')
 * @var ?int    $categoryId   Selected category ID or null
 * @var ?string $zip          Zip code filter or null
 * @var ?int    $radius       Search radius in miles or null
 * @var ?float  $maxFee       Max rental fee or null
 * @var int     $sliderMax    Rounded ceiling for the fee range slider
 * @var int     $sliderValue  Current slider position
 * @var bool    $radiusAutoApplied  True when 50-mile default was auto-applied
 * @var bool    $availableOnly  True when rendered by AvailableController
 */

$isAvailable = !empty($availableOnly);
$basePath    = $isAvailable ? '/available' : '/tools';

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$paginationUrl = static function (int $pageNum) use ($filterParams, $basePath): string {
    $params = array_merge($filterParams, ['page' => $pageNum]);
    return $basePath . '?' . htmlspecialchars(http_build_query($params));
};
?>

<section id="browse-page" aria-labelledby="browse-heading">

  <header>
    <h1 id="browse-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
      <?= $isAvailable ? 'Available Tools' : 'All Tools' ?>
    </h1>
    <nav aria-label="Browse mode">
      <a href="/available"<?= $isAvailable ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-check-circle" aria-hidden="true"></i> Available
      </a>
      <a href="/tools"<?= !$isAvailable ? ' aria-current="page"' : '' ?>>
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> All Tools
      </a>
    </nav>
  </header>

  <?php if (!empty($bookmarkFlash)): ?>
    <p role="status" data-flash="success"><?= htmlspecialchars($bookmarkFlash) ?></p>
  <?php endif; ?>

  <?php if (!empty($zipWarning)): ?>
    <p role="alert">
      <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
      <?= htmlspecialchars($zipWarning) ?>
    </p>
  <?php endif; ?>

  <form role="search" action="<?= htmlspecialchars($basePath) ?>" method="get" aria-label="Search and filter tools"
        data-default-zip="<?= htmlspecialchars($userZip ?? '') ?>"
        data-default-radius="<?= $radiusAutoApplied ? '50' : '' ?>">

    <fieldset aria-label="Search">
      <legend class="visually-hidden">Search</legend>
      <label for="browse-search" class="visually-hidden">Search tools</label>
      <span aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
      <input type="search"
             id="browse-search"
             name="q"
             placeholder="Search tools by name or description…"
             value="<?= htmlspecialchars($term) ?>"
             autocomplete="off"
             data-suggest="tools"
             <?= $isAvailable ? 'data-available-only' : '' ?>>
      <button type="submit" data-intent="primary" data-shape="pill">
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        <span>Search</span>
      </button>
    </fieldset>

    <fieldset aria-label="Filters">
      <legend class="visually-hidden">Filter options</legend>

      <div>
        <label for="filter-category">
          <i class="fa-solid fa-tags" aria-hidden="true"></i> Category
        </label>
        <select id="filter-category" name="category">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id_cat'] ?>"
                    <?= $categoryId === (int) $cat['id_cat'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['category_name_cat']) ?> (<?= $browseCounts[(int) $cat['id_cat']] ?? 0 ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="filter-zip">
          <i class="fa-solid fa-location-dot" aria-hidden="true"></i> Zip Code
        </label>
        <input type="text"
               id="filter-zip"
               name="zip"
               placeholder="<?= htmlspecialchars($userZip ?? 'e.g. 28801') ?>"
               value="<?= htmlspecialchars($zip ?? '') ?>"
               pattern="[0-9]{5}"
               maxlength="5"
               inputmode="numeric"
               autocomplete="postal-code">
      </div>

      <div>
        <label for="filter-radius">
          <i class="fa-solid fa-circle-dot" aria-hidden="true"></i> Distance
        </label>
        <select id="filter-radius" name="radius">
          <option value="">Zip code required</option>
          <option value="5" <?= $radius === 5 ? 'selected' : '' ?>>5 miles</option>
          <option value="10" <?= $radius === 10 ? 'selected' : '' ?>>10 miles</option>
          <option value="25" <?= $radius === 25 ? 'selected' : '' ?>>25 miles</option>
          <option value="50" <?= $radius === 50 ? 'selected' : '' ?>>50 miles</option>
        </select>
      </div>

      <div>
        <label for="filter-max-fee">
          <i class="fa-solid fa-dollar-sign" aria-hidden="true"></i>
          Max Fee:
        </label>
        <output id="fee-display" for="filter-max-fee">$<?= htmlspecialchars((string) $sliderValue) ?></output>
        <input type="range"
               id="filter-max-fee"
               name="max_fee"
               min="0"
               max="<?= htmlspecialchars((string) $sliderMax) ?>"
               step="1"
               value="<?= htmlspecialchars((string) $sliderValue) ?>"
               aria-valuenow="<?= htmlspecialchars((string) $sliderValue) ?>"
               aria-valuetext="$<?= htmlspecialchars((string) $sliderValue) ?>">
        <span>
          <span>$0</span>
          <span>$<?= htmlspecialchars((string) $sliderMax) ?></span>
        </span>
      </div>

      <button type="submit" data-intent="primary" data-shape="pill">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply Filters
      </button>

      <?php if (!empty($filterParams)): ?>
        <a href="<?= htmlspecialchars($basePath) ?>" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear Filters
        </a>
      <?php endif; ?>
    </fieldset>

  </form>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>&ndash;<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        tool<?= $totalCount !== 1 ? 's' : '' ?><?php if ($zip !== null && $radius !== null): ?>
        within <strong><?= (int) $radius ?></strong> miles of <strong><?= htmlspecialchars($zip) ?></strong><?php endif; ?>
      </p>
    <?php else: ?>
      <p>No tools match your filters.</p>
    <?php endif; ?>
  </div>

  <?php $hasTools = !empty($tools); ?>

    <div role="list" data-tool-preview <?= $hasTools ? '' : 'hidden' ?>>
      <?php if ($hasTools): ?>
        <?php $cardSizes = '(max-width: 600px) calc(100vw - 3rem), (max-width: 900px) calc(50vw - 2.25rem), 270px'; ?>
        <?php foreach ($tools as $tool): ?>
          <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
        <?php endforeach; ?>
        <?php unset($cardSizes); ?>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Pagination">
        <ul>

          <?php if ($page > 1): ?>
            <li>
              <a href="<?= $paginationUrl($page - 1) ?>"
                 aria-label="Go to previous page">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span>Previous</span>
              </a>
            </li>
          <?php else: ?>
            <li>
              <span aria-disabled="true">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                <span>Previous</span>
              </span>
            </li>
          <?php endif; ?>

          <?php
          // Show a window of page numbers around the current page
          $windowSize = 2;
          $startPage  = max(1, $page - $windowSize);
          $endPage    = min($totalPages, $page + $windowSize);

          // Always show first page
          if ($startPage > 1): ?>
            <li>
              <a href="<?= $paginationUrl(1) ?>" aria-label="Go to page 1">1</a>
            </li>
            <?php if ($startPage > 2): ?>
              <li><span aria-hidden="true">…</span></li>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li>
              <?php if ($i === $page): ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-current="page"
                   aria-label="Page <?= htmlspecialchars((string) $i) ?>, current page"><?= htmlspecialchars((string) $i) ?></a>
              <?php else: ?>
                <a href="<?= $paginationUrl($i) ?>"
                   aria-label="Go to page <?= htmlspecialchars((string) $i) ?>"><?= htmlspecialchars((string) $i) ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>

          <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
              <li><span aria-hidden="true">…</span></li>
            <?php endif; ?>
            <li>
              <a href="<?= $paginationUrl($totalPages) ?>"
                 aria-label="Go to page <?= htmlspecialchars((string) $totalPages) ?>"><?= htmlspecialchars((string) $totalPages) ?></a>
            </li>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <li>
              <a href="<?= $paginationUrl($page + 1) ?>"
                 aria-label="Go to next page">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </a>
            </li>
          <?php else: ?>
            <li>
              <span aria-disabled="true">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </span>
            </li>
          <?php endif; ?>

        </ul>
      </nav>
    <?php endif; ?>

    <section aria-label="No results" <?= $hasTools ? 'hidden' : '' ?>>
      <img src="/assets/images/empty-search.svg" alt="" width="200" height="200">
      <h2>No Tools Found</h2>
      <?php if (!empty($filterParams)): ?>
        <ul>
          <?php if ($term !== ''): ?>
            <li>No tools match &ldquo;<?= htmlspecialchars($term) ?>&rdquo; &mdash; try a broader search term</li>
          <?php endif; ?>
          <?php if ($radius !== null && $radius < 50): ?>
            <li>
              Try <a href="<?= htmlspecialchars($basePath) ?>?<?= http_build_query(array_merge($filterParams, ['radius' => 50])) ?>">increasing your search distance</a>
            </li>
          <?php endif; ?>
          <?php if ($maxFee !== null): ?>
            <li>Try raising or removing the max fee filter</li>
          <?php endif; ?>
        </ul>
        <a href="<?= htmlspecialchars($basePath) ?>" role="button" data-intent="ghost">
          <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Clear All Filters
        </a>
      <?php else: ?>
        <p>No tools are currently available &mdash; check back soon.</p>
      <?php endif; ?>
    </section>

</section>
