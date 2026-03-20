<?php

/**
 * Admin — Tool management with analytics from tool_statistics_fast_v.
 *
 * Variables from AdminController::tools():
 *   $tools         array   Rows from Tool::getAdminList() via tool_statistics_fast_v
 *   $totalCount    int     Total tools matching current filters
 *   $page          int     Current page (1-based)
 *   $totalPages    int     Total pages
 *   $perPage       int     Results per page (12)
 *   $search        ?string Active search query or null
 *   $condition     ?string Active condition filter or null
 *   $incidentsOnly bool    Whether filtering to tools with incidents
 *   $sort          string  Active sort column
 *   $dir           string  Active sort direction (ASC|DESC)
 *   $filterParams  array   Non-null filter params for pagination URLs
 *
 * Each tool row contains:
 *   id_tol, tool_name_tol, owner_id, owner_name, tool_condition,
 *   rental_fee_tol, estimated_value_tol, created_at_tol,
 *   avg_rating, rating_count, five_star_count,
 *   total_borrows, completed_borrows, cancelled_borrows, denied_borrows,
 *   total_hours_borrowed, last_borrowed_at,
 *   incident_count, refreshed_at
 *
 * Shared data:
 *   $currentPage  string
 *   $backUrl      string
 */

$rangeStart = $totalCount > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd   = min($page * $perPage, $totalCount);

$basePath = '/admin/tools';

$sortLabels = [
  'tool_name_tol'  => 'Name',
  'owner_name'     => 'Owner',
  'tool_condition'  => 'Condition',
  'rental_fee_tol' => 'Fee',
  'avg_rating'     => 'Rating',
  'total_borrows'  => 'Borrows',
  'incident_count' => 'Incidents',
  'created_at_tol' => 'Listed',
];

$sortToColumn = [
  'tool_name_tol'  => 0,
  'owner_name'     => 1,
  'tool_condition'  => 2,
  'rental_fee_tol' => 3,
  'avg_rating'     => 4,
  'total_borrows'  => 5,
  'incident_count' => 6,
  'created_at_tol' => 7,
];

$ariaSortDir = $dir === 'ASC' ? 'ascending' : 'descending';
$hasFilters  = $search !== null || $condition !== null || $incidentsOnly;
?>

<section aria-labelledby="admin-tools-heading">

  <header>
    <h1 id="admin-tools-heading">
      <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
      Manage Tools
    </h1>
    <p>Platform-wide tool listings with borrow statistics, ratings, and incident counts.</p>
  </header>

  <?php require BASE_PATH . '/src/Views/partials/admin-nav.php'; ?>

  <form method="get" action="/admin/tools" role="search" aria-label="Filter and sort tools" data-admin-filters>
    <fieldset>
      <legend class="visually-hidden">Filter and sort tools</legend>

      <div>
        <label for="tools-search">Search</label>
        <input type="search" id="tools-search" name="q"
          value="<?= htmlspecialchars($search ?? '') ?>"
          placeholder="Tool name or owner…"
          autocomplete="off"
          data-suggest="admin" data-suggest-type="tools">
      </div>

      <div>
        <label for="tools-condition">Condition</label>
        <select id="tools-condition" name="condition">
          <option value="">All Conditions</option>
          <option value="new" <?= $condition === 'new' ? ' selected' : '' ?>>New</option>
          <option value="good" <?= $condition === 'good' ? ' selected' : '' ?>>Good</option>
          <option value="fair" <?= $condition === 'fair' ? ' selected' : '' ?>>Fair</option>
          <option value="poor" <?= $condition === 'poor' ? ' selected' : '' ?>>Poor</option>
        </select>
      </div>

      <div>
        <label for="tools-sort">Sort By</label>
        <select id="tools-sort" name="sort">
          <?php foreach ($sortLabels as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $sort === $value ? ' selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="tools-dir">Direction</label>
        <select id="tools-dir" name="dir">
          <option value="asc" <?= $dir === 'ASC' ? ' selected' : '' ?>>Ascending</option>
          <option value="desc" <?= $dir === 'DESC' ? ' selected' : '' ?>>Descending</option>
        </select>
      </div>

      <div>
        <input type="checkbox" id="tools-incidents" name="incidents" value="1" <?= $incidentsOnly ? ' checked' : '' ?>>
        <label for="tools-incidents">Incidents only</label>
      </div>

      <button type="submit" data-intent="primary" data-shape="pill">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply
      </button>
      <?php if ($hasFilters): ?>
        <a href="<?= htmlspecialchars($basePath) ?>" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
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
    <?php endif; ?>
  </div>

  <?php if (!empty($tools)): ?>

    <table>
      <caption class="visually-hidden">Listed tools and their status</caption>
      <thead>
        <tr>
          <?php
          $columns = ['Tool', 'Owner', 'Condition', 'Fee', 'Rating', 'Borrows', 'Incidents', 'Listed'];
          foreach ($columns as $i => $label):
            $isSorted = isset($sortToColumn[$sort]) && $sortToColumn[$sort] === $i;
          ?>
            <th scope="col" <?= $isSorted ? ' aria-sort="' . $ariaSortDir . '"' : '' ?>><?= $label ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tools as $tool):
          $incidents = (int) $tool['incident_count'];
        ?>
          <tr<?= $incidents > 0 ? ' data-has-incidents' : '' ?>>
            <td data-label="Tool">
              <a href="/tools/<?= (int) $tool['id_tol'] ?>">
                <?= htmlspecialchars($tool['tool_name_tol']) ?>
              </a>
              <small>$<?= number_format((float) $tool['rental_fee_tol'], 2) ?>/day</small>
            </td>
            <td data-label="Owner">
              <a href="/profile/<?= (int) $tool['owner_id'] ?>">
                <?= htmlspecialchars($tool['owner_name']) ?>
              </a>
            </td>
            <td data-label="Condition">
              <span data-condition="<?= htmlspecialchars($tool['tool_condition']) ?>">
                <?= htmlspecialchars(ucfirst($tool['tool_condition'])) ?>
              </span>
            </td>
            <td data-label="Fee">$<?= number_format((float) $tool['rental_fee_tol'], 2) ?></td>
            <td data-label="Rating">
              <?php if ((int) $tool['rating_count'] > 0): ?>
                <span><?= htmlspecialchars($tool['avg_rating']) ?></span>
                <small>(<?= number_format((int) $tool['rating_count']) ?>)</small>
              <?php else: ?>
                <span>—</span>
              <?php endif; ?>
            </td>
            <td data-label="Borrows">
              <span><?= number_format((int) $tool['completed_borrows']) ?></span>
              <small>of <?= number_format((int) $tool['total_borrows']) ?></small>
            </td>
            <td data-label="Incidents">
              <?php if ($incidents > 0): ?>
                <span data-warning><?= $incidents ?></span>
              <?php else: ?>
                <span>0</span>
              <?php endif; ?>
            </td>
            <td data-label="Listed">
              <time datetime="<?= htmlspecialchars($tool['created_at_tol']) ?>">
                <?= htmlspecialchars(date('M j, Y', strtotime($tool['created_at_tol']))) ?>
              </time>
            </td>
            </tr>
          <?php endforeach; ?>
      </tbody>
    </table>

    <?php require BASE_PATH . '/src/Views/partials/pagination.php'; ?>

  <?php else: ?>

    <section aria-label="No tools">
      <i class="fa-regular fa-face-smile" aria-hidden="true"></i>
      <h2>No Tools Listed</h2>
      <p>No tools match the current criteria.</p>
      <?php if ($hasFilters): ?>
        <a href="/admin/tools" role="button" data-intent="ghost">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($backUrl) ?>" role="button" data-intent="secondary">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
        </a>
      <?php endif; ?>
    </section>

  <?php endif; ?>

  </div>
</section>