<?php
/**
 * Browse Tools — search, filter, and paginate available tools.
 *
 * Variables from ToolController::index():
 *   $tools        array   Tool rows from sp_search_available_tools()
 *   $categories   array   Rows from category_summary_v
 *   $totalCount   int     Total matching tools (for pagination text)
 *   $page         int     Current page number (1-based)
 *   $totalPages   int     Total pages
 *   $perPage      int     Results per page (12)
 *   $filterParams array   Active filters (q, category, zip, max_fee) — nulls stripped
 *   $term         string  Current search term (may be '')
 *   $categoryId   ?int    Selected category ID or null
 *   $zip          ?string Zip code filter or null
 *   $maxFee       ?float  Max rental fee or null
 *   $sliderMax    int     Rounded ceiling for the fee range slider
 *   $sliderValue  int     Current slider position (user-set or sliderMax)
 */

// Pagination range display
$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

/**
 * Build a pagination URL preserving all active filters.
 */
$paginationUrl = static function (int $pageNum) use ($filterParams): string {
    $params = array_merge($filterParams, ['page' => $pageNum]);
    return '/tools?' . htmlspecialchars(http_build_query($params));
};
?>

<section id="browse-page" aria-labelledby="browse-heading">

  <header>
    <h1 id="browse-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Browse Tools
    </h1>
    <p>Find the right tool from your neighbors in the Asheville and Hendersonville areas.</p>
  </header>

  <?php if (!empty($_SESSION['bookmark_flash'])): ?>
    <p role="status"><?= htmlspecialchars($_SESSION['bookmark_flash']) ?></p>
    <?php unset($_SESSION['bookmark_flash']); ?>
  <?php endif; ?>

  <form role="search" action="/tools" method="get" aria-label="Search and filter tools">

    <fieldset aria-label="Search">
      <label for="browse-search" class="visually-hidden">Search tools</label>
      <span aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
      <input type="search"
             id="browse-search"
             name="q"
             placeholder="Search tools by name or description…"
             value="<?= htmlspecialchars($term) ?>"
             autocomplete="off">
      <button type="submit">
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
              <?= htmlspecialchars($cat['category_name_cat']) ?> (<?= (int) $cat['available_tools'] ?>)
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
               placeholder="e.g. 28801"
               value="<?= htmlspecialchars($zip ?? '') ?>"
               pattern="[0-9]{5}"
               maxlength="5"
               inputmode="numeric"
               autocomplete="postal-code">
      </div>

      <div>
        <label for="filter-max-fee">
          <i class="fa-solid fa-dollar-sign" aria-hidden="true"></i>
          Max Fee: <output for="filter-max-fee" id="fee-display">$<?= htmlspecialchars((string) $sliderValue) ?></output>
        </label>
        <input type="range"
               id="filter-max-fee"
               name="max_fee"
               min="0"
               max="<?= htmlspecialchars((string) $sliderMax) ?>"
               step="1"
               value="<?= htmlspecialchars((string) $sliderValue) ?>"
               aria-valuemin="0"
               aria-valuemax="<?= htmlspecialchars((string) $sliderMax) ?>"
               aria-valuenow="<?= htmlspecialchars((string) $sliderValue) ?>"
               aria-valuetext="$<?= htmlspecialchars((string) $sliderValue) ?>">
        <span>
          <span>$0</span>
          <span>$<?= htmlspecialchars((string) $sliderMax) ?></span>
        </span>
      </div>

      <button type="submit">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply Filters
      </button>

      <?php if (!empty($filterParams)): ?>
        <a href="/tools" role="button">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear Filters
        </a>
      <?php endif; ?>
    </fieldset>

  </form>

  <div aria-live="polite" aria-atomic="true">
    <?php if ($totalCount > 0): ?>
      <p>
        Showing <strong><?= htmlspecialchars((string) $rangeStart) ?>–<?= htmlspecialchars((string) $rangeEnd) ?></strong> of
        <strong><?= number_format($totalCount) ?></strong>
        tool<?= $totalCount !== 1 ? 's' : '' ?>
      </p>
    <?php else: ?>
      <p>No tools match your filters.</p>
    <?php endif; ?>
  </div>

  <?php if (!empty($tools)): ?>

    <div role="list">
      <?php foreach ($tools as $tool): ?>
        <?php require BASE_PATH . '/src/Views/partials/tool-card.php'; ?>
      <?php endforeach; ?>
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
              <span aria-disabled="true" aria-label="No previous page">
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
              <span aria-disabled="true" aria-label="No next page">
                <span>Next</span>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              </span>
            </li>
          <?php endif; ?>

        </ul>
      </nav>
    <?php endif; ?>

  <?php else: ?>

    <section aria-label="No results">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <h2>No Tools Found</h2>
      <p>Try broadening your search or adjusting the filters above.</p>
      <?php if (!empty($filterParams)): ?>
        <a href="/tools" role="button">
          <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Clear All Filters
        </a>
      <?php endif; ?>
    </section>

  <?php endif; ?>

</section>
